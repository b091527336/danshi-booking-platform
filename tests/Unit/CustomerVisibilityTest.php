<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;

class CustomerVisibilityTest extends TestCase
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
            'app.key' => 'base64:'.base64_encode(str_repeat('x', 32)),
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        $this->artisan('migrate:fresh')->assertExitCode(0);
    }

    public function test_customer_list_and_detail_count_only_bookings_the_user_can_see(): void
    {
        [$allowed, $other] = Organization::query()->orderBy('id')->take(2)->get()->all();
        $shared = Customer::create(['name' => 'Shared customer']);
        $otherOnly = Customer::create(['name' => 'Other location only']);
        foreach ([[$allowed, $shared], [$other, $shared], [$other, $otherOnly]] as $index => [$organization, $customer]) {
            Booking::create([
                'organization_id' => $organization->id,
                'customer_id' => $customer->id,
                'external_id' => 'privacy-test-'.$index,
                'starts_at' => '2026-11-01 10:00:00',
                'service_name' => 'privacy-test-'.$index,
            ]);
        }

        $client = $this->account('client');
        $client->organizations()->attach($allowed);
        $this->actingAs($client)->get('/customers')
            ->assertOk()
            ->assertSee('Shared customer')
            ->assertDontSee('Other location only')
            ->assertViewHas('customers', fn ($customers) => $customers->count() === 1
                && (int) $customers->first()->bookings_count === 1);
        $this->get('/customers/'.$shared->id)
            ->assertOk()
            ->assertSee('privacy-test-0')
            ->assertDontSee('privacy-test-1')
            ->assertViewHas('customer', fn ($customer) => (int) $customer->bookings_count === 1);
        $this->get('/customers/'.$otherOnly->id)->assertForbidden();

        $this->actingAs($this->account('admin'))->get('/customers')
            ->assertOk()
            ->assertViewHas('customers', fn ($customers) => $customers->count() === 2
                && (int) $customers->getCollection()->firstWhere('id', $shared->id)->bookings_count === 2);
    }

    private function account(string $role): User
    {
        return User::create([
            'name' => 'Privacy tester',
            'email' => $role.'@example.test',
            'role' => $role,
            'password' => 'test-password-only',
        ]);
    }
}
