<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\BookingNotification;
use App\Models\Customer;
use App\Models\LinePairingCode;
use App\Models\LineRecipient;
use App\Models\Organization;
use App\Models\User;
use App\Services\BookingNotificationService;
use App\Services\BookingNotificationTemplate;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BookingNotificationsTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.key'=>'base64:'.base64_encode(str_repeat('x',32)),
            'database.default'=>'sqlite',
            'database.connections.sqlite'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true],
            'services.line.channels'=>['anasa'=>['secret'=>'test-secret','token'=>'test-token']],
        ]);
        $this->artisan('migrate:fresh')->assertExitCode(0);
        $this->travelTo(now()->setDate(2026,10,11)->setTime(12,0));
        Http::preventStrayRequests();
    }

    private function user(string $role='client'): User
    {
        return User::create(['name'=>'Test','email'=>Str::uuid().'@example.test','password'=>'test-password','role'=>$role]);
    }

    private function organization(): Organization
    {
        $org=Organization::first();
        $org->update(['settings'=>['booking_url'=>'keep-existing','notifications'=>array_merge(BookingNotificationTemplate::defaults(),[
            'staff_enabled'=>true,'customer_enabled'=>true,'channel'=>'anasa',
        ])]]);
        return $org;
    }

    private function booking(Organization $org): Booking
    {
        $customer=Customer::create(['name'=>'Test Customer','email'=>'test@example.test']);
        return Booking::create(['organization_id'=>$org->id,'customer_id'=>$customer->id,'external_id'=>(string)Str::uuid(),
            'starts_at'=>now()->addDays(2),'status'=>'confirmed','service_name'=>'安裝預約']);
    }

    public function test_settings_are_visible_to_client_and_do_not_expose_other_locations(): void
    {
        $user=$this->user(); $org=$this->organization(); $user->organizations()->attach($org);
        $other=Organization::where('id','!=',$org->id)->first();
        $this->actingAs($user)->get('/notifications')->assertOk()->assertSee('工作人員通知')->assertSee('客人通知')->assertDontSee($other->name);
    }

    public function test_bulk_save_preserves_existing_settings_and_cannot_modify_unassigned_org(): void
    {
        $user=$this->user(); $org=$this->organization(); $user->organizations()->attach($org);
        $data=['organization_ids'=>[$org->id],'staff_enabled'=>1,'staff_template'=>'Hello {{客戶姓名}}','customer_template'=>'{{預約時間}}'];
        $this->actingAs($user)->put('/notifications',$data)->assertRedirect();
        $this->assertSame('keep-existing',$org->fresh()->settings['booking_url']);
        $this->assertSame('anasa',$org->fresh()->settings['notifications']['channel']);
        $other=Organization::where('id','!=',$org->id)->first();
        $this->putJson('/notifications',array_replace($data,['organization_ids'=>[$org->id,$other->id]]))->assertUnprocessable();
        $this->assertNull($other->fresh()->settings['notifications']??null);
    }

    public function test_unsupported_placeholders_and_empty_content_are_rejected(): void
    {
        $org=$this->organization(); $this->actingAs($this->user('admin'));
        $this->putJson('/notifications',['organization_ids'=>[$org->id],'staff_template'=>'{{秘密}}','customer_template'=>'Hi'])->assertUnprocessable();
        $this->putJson('/notifications',['organization_ids'=>[$org->id],'staff_template'=>'{{broken','customer_template'=>'Hi'])->assertUnprocessable();
    }

    public function test_guest_must_sign_in(): void
    {
        $this->get('/notifications')->assertRedirect('/login');
    }

    public function test_repeated_enqueue_is_deduplicated_and_prior_pending_change_is_superseded(): void
    {
        $booking=$this->booking($this->organization()); $service=app(BookingNotificationService::class);
        $service->enqueue($booking,'新預約已收到'); $service->enqueue($booking,'新預約已收到');
        $this->assertSame(2,BookingNotification::count());
        $booking->update(['starts_at'=>now()->addDays(3)]); $service->enqueue($booking,'預約已更新');
        $this->assertSame(2,BookingNotification::where('status','superseded')->count());
        $this->assertSame(2,BookingNotification::where('status','pending')->count());
        $this->assertStringNotContainsString('Test Customer',\Illuminate\Support\Facades\DB::table('booking_notifications')->value('body'));
    }

    public function test_disabled_and_past_bookings_are_not_enqueued(): void
    {
        $org=$this->organization(); $booking=$this->booking($org); $booking->update(['starts_at'=>now()->subDay()]);
        app(BookingNotificationService::class)->enqueue($booking,'新預約已收到');
        $booking->update(['starts_at'=>now()->addDay()]); $org->update(['settings'=>[]]);
        app(BookingNotificationService::class)->enqueue($booking->fresh(),'新預約已收到');
        $this->assertSame(0,BookingNotification::count());
    }

    public function test_missing_binding_is_pending_and_never_sends_to_customer_by_phone(): void
    {
        $booking=$this->booking($this->organization()); $service=app(BookingNotificationService::class); $service->enqueue($booking,'新預約已收到');
        $notification=BookingNotification::where('audience','customer')->first(); $service->deliver($notification);
        $this->assertSame('LINE_NOT_CONNECTED',$notification->fresh()->error_code); Http::assertNothingSent();
    }

    public function test_push_uses_only_bound_recipient_and_reuses_retry_key(): void
    {
        $org=$this->organization(); $booking=$this->booking($org); $service=app(BookingNotificationService::class); $service->enqueue($booking,'新預約已收到');
        $recipient='C'.str_repeat('a',32);
        LineRecipient::create(['organization_id'=>$org->id,'audience'=>'staff','recipient_key'=>$org->id.':staff:group','channel'=>'anasa','line_id'=>$recipient]);
        Http::fakeSequence()->push([],500)->push([],200);
        $notification=BookingNotification::where('audience','staff')->first(); $key=$notification->retry_key;
        $service->deliver($notification); $service->deliver($notification->fresh());
        $this->assertSame('sent',$notification->fresh()->status);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['to']===$recipient && $request->hasHeader('X-Line-Retry-Key',$key));
    }

    public function test_binding_requires_signed_webhook_and_single_use_code(): void
    {
        $org=$this->organization(); $admin=$this->user('admin'); $raw=Str::random(32);
        $code=LinePairingCode::create(['code_hash'=>hash('sha256',$raw),'channel'=>'anasa','audience'=>'staff','organization_ids'=>[$org->id],'issued_by'=>$admin->id,'expires_at'=>now()->addMinutes(30)]);
        $payload=json_encode(['events'=>[['type'=>'message','message'=>['type'=>'text','text'=>'DBP-'.$raw],'source'=>['type'=>'group','groupId'=>'C'.str_repeat('b',32)]]]]);
        $this->call('POST','/line/webhook/anasa',[],[],[],['CONTENT_TYPE'=>'application/json'],$payload)->assertForbidden();
        $headers=['CONTENT_TYPE'=>'application/json','HTTP_X_LINE_SIGNATURE'=>base64_encode(hash_hmac('sha256',$payload,'test-secret',true))];
        $this->call('POST','/line/webhook/anasa',[],[],[],$headers,$payload)->assertOk();
        $this->assertNotNull($code->fresh()->used_at); $this->assertSame(1,LineRecipient::count());
        $this->call('POST','/line/webhook/anasa',[],[],[],$headers,$payload)->assertOk();
        $this->assertSame(1,LineRecipient::count());
    }

    public function test_expired_and_wrong_source_pairing_codes_cannot_bind(): void
    {
        $org=$this->organization(); $raw=Str::random(32);
        LinePairingCode::create(['code_hash'=>hash('sha256',$raw),'channel'=>'anasa','audience'=>'staff','organization_ids'=>[$org->id],'issued_by'=>$this->user('admin')->id,'expires_at'=>now()->subMinute()]);
        $payload=json_encode(['events'=>[['type'=>'message','message'=>['type'=>'text','text'=>'DBP-'.$raw],'source'=>['type'=>'group','groupId'=>'C'.str_repeat('b',32)]]]]);
        $headers=['CONTENT_TYPE'=>'application/json','HTTP_X_LINE_SIGNATURE'=>base64_encode(hash_hmac('sha256',$payload,'test-secret',true))];
        $this->call('POST','/line/webhook/anasa',[],[],[],$headers,$payload)->assertOk();
        $this->assertSame(0,LineRecipient::count());
    }

    public function test_customer_pairing_cannot_reference_customer_from_other_organization(): void
    {
        $org=$this->organization(); $other=Organization::where('id','!=',$org->id)->first(); $booking=$this->booking($other);
        $user=$this->user();$user->organizations()->attach($org);
        $this->actingAs($user)->postJson('/notifications/pair',['organization_ids'=>[$org->id],'audience'=>'customer','customer_id'=>$booking->customer_id])->assertForbidden();
    }

    public function test_permanent_api_errors_do_not_retry_and_expired_jobs_do_not_send(): void
    {
        $org=$this->organization(); $booking=$this->booking($org); $service=app(BookingNotificationService::class);$service->enqueue($booking,'新預約已收到');
        LineRecipient::create(['organization_id'=>$org->id,'audience'=>'staff','recipient_key'=>$org->id.':staff:group','channel'=>'anasa','line_id'=>'C'.str_repeat('c',32)]);
        Http::fakeSequence()->push([],400);
        $notification=BookingNotification::where('audience','staff')->first();$service->deliver($notification);$this->assertSame('failed',$notification->fresh()->status);
        $customer=BookingNotification::where('audience','customer')->first();$customer->update(['created_at'=>now()->subHours(13)]);$service->deliver($customer->fresh());
        $this->assertSame('expired',$customer->fresh()->status);Http::assertSentCount(1);
    }
}
