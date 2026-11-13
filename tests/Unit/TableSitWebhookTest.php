<?php
namespace Tests\Unit;

use App\Models\Organization;
use App\Services\TableSit\WebhookSignature;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TableSitWebhookTest extends TestCase
{
    public function createApplication()
    {
        putenv('DB_CONNECTION=sqlite'); putenv('DB_DATABASE=:memory:');
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key'=>'base64:'.base64_encode(str_repeat('x',32)), 'services.tablesit.webhook_secret'=>'whsec_testing', 'services.tablesit.partner_api_key'=>'test']);
        $this->artisan('migrate', ['--force'=>true]);
        Organization::create(['name'=>'Demo','slug'=>'demo','external_provider'=>'tablesit','external_id'=>'550e8400-e29b-41d4-a716-446655440001','timezone'=>'Asia/Taipei','is_active'=>true]);
    }

    private function event(bool $live = true, string $type = 'booking.created'): array
    {
        return ['id'=>'evt_test_1','type'=>$type,'livemode'=>$live,'organization'=>['uid'=>'550e8400-e29b-41d4-a716-446655440001'], 'data'=>['object'=>['uid'=>'550e8400-e29b-41d4-a716-446655440000']]];
    }

    private function sendEvent(array $event, ?string $signature = null)
    {
        $raw = json_encode($event);
        return $this->call('POST','/tablesit/webhook', [], [], [], ['CONTENT_TYPE'=>'application/json','HTTP_TABLESIT_SIGNATURE'=>$signature ?? 't='.time().',v1='.hash_hmac('sha256',time().'.'.$raw,'whsec_testing')],$raw);
    }

    public function test_signature_checks_raw_bytes_timestamp_and_multiple_signatures(): void
    {
        $s = new WebhookSignature(); $body = '{ "id": 1 }'; $t = time();
        $header = 't='.$t.',v1='.str_repeat('0',64).',v1='.hash_hmac('sha256',$t.'.'.$body,'whsec_testing');
        $this->assertTrue($s->valid($body,$header,'whsec_testing',$t));
        $this->assertFalse($s->valid('{"id":1}',$header,'whsec_testing',$t));
        $this->assertFalse($s->valid($body,$header,'whsec_testing',$t+301));
        $this->assertFalse($s->valid($body,$header,'',$t));
        $this->assertFalse($s->valid($body,$header.',t='.$t,'whsec_testing',$t));
    }

    public function test_inbox_deduplicates_and_rejects_invalid_signature(): void
    {
        $this->sendEvent($this->event(), 't=1,v1=bad')->assertStatus(401);
        $this->assertSame(0,DB::table('tablesit_webhook_events')->count());
        $this->sendEvent($this->event())->assertStatus(202);
        $this->sendEvent($this->event())->assertStatus(202);
        $this->assertSame(1,DB::table('tablesit_webhook_events')->count());
    }

    public function test_test_events_never_create_bookings_or_call_api(): void
    {
        Http::preventStrayRequests();
        $this->sendEvent($this->event(false))->assertStatus(202);
        $this->artisan('tablesit:process-webhooks')->assertExitCode(0);
        $this->assertSame('test',DB::table('tablesit_webhook_events')->value('status'));
        $this->assertSame(0, DB::table('bookings')->count());
    }

    public function test_unknown_organization_is_ignored(): void
    {
        $event=$this->event();$event['organization']['uid']='550e8400-e29b-41d4-a716-446655440099';
        $this->sendEvent($event)->assertStatus(202);
        $this->assertSame('ignored',DB::table('tablesit_webhook_events')->value('status'));
    }

    public function test_reconciliation_uses_latest_api_state_and_retries_failures(): void
    {
        $this->sendEvent($this->event())->assertStatus(202);
        Http::fake(['*'=>Http::response(['data'=>['uid'=>'550e8400-e29b-41d4-a716-446655440000','start_at'=>'2026-12-12T02:00:00Z','status'=>'cancelled','updated_at'=>'2026-11-11T12:00:00Z']],200)]);
        $this->artisan('tablesit:process-webhooks')->assertExitCode(0);
        $this->assertSame('cancelled',DB::table('bookings')->value('status'));
        $this->assertSame('processed',DB::table('tablesit_webhook_events')->value('status'));
        $event=$this->event();$event['id']='evt_test_2';$this->sendEvent($event)->assertStatus(202);
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*'=>Http::response([],503)]);
        $this->artisan('tablesit:process-webhooks')->assertExitCode(0);
        $this->assertSame('pending',DB::table('tablesit_webhook_events')->where('event_id','evt_test_2')->value('status'));
        $this->assertSame(1,DB::table('tablesit_webhook_events')->where('event_id','evt_test_2')->value('attempts'));
    }
    public function test_deleted_bookings_remain_cancelled_when_old_poll_results_arrive(): void
    {
        $organization=Organization::where('slug','demo')->firstOrFail();
        $sync=app(\App\Services\TableSit\BookingSyncService::class);
        $payload=['uid'=>'550e8400-e29b-41d4-a716-446655440000','start_at'=>'2026-12-12T02:00:00Z','status'=>'confirmed','updated_at'=>'2026-10-11T12:00:00Z'];
        $sync->syncOne($organization,$payload);
        $this->sendEvent($this->event(true,'booking.deleted'))->assertStatus(202);
        Http::fake(['*'=>Http::response([],404)]);
        $this->artisan('tablesit:process-webhooks')->assertExitCode(0);
        $this->assertSame('processed',DB::table('tablesit_webhook_events')->value('status'));
        $sync->syncOne($organization,$payload);
        $this->assertSame('cancelled',DB::table('bookings')->value('status'));
        $this->assertSame(1,DB::table('bookings')->count());
    }

    public function test_no_secret_keeps_receiver_closed(): void
    {
        config(['services.tablesit.webhook_secret'=>null]);
        $this->sendEvent($this->event())->assertStatus(503);
        $this->assertSame(0,DB::table('tablesit_webhook_events')->count());
    }

}
