<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;

class BookingCalendarTest extends TestCase
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

    private function account(string $role): User
    {
        return User::create([
            'name' => 'Calendar Tester', 'email' => $role.'@example.test',
            'password' => 'test-password-only', 'role' => $role,
        ]);
    }

    private function booking(Organization $organization, string $id, string $date, string $status = 'confirmed'): Booking
    {
        return Booking::create([
            'organization_id' => $organization->id, 'external_id' => $id,
            'starts_at' => $date, 'status' => $status, 'service_name' => $id,
        ]);
    }

    public function test_calendar_combines_authorized_locations_and_does_not_paginate_events(): void
    {
        $organizations = Organization::query()->take(2)->get();
        for ($index = 0; $index < 25; $index++) {
            $this->booking($organizations[$index % 2], 'event-'.$index, '2026-10-12 09:00:00');
        }

        $this->actingAs($this->account('admin'))->get('/bookings?month=2026-10')
            ->assertOk()->assertViewIs('bookings.calendar')->assertViewHas('bookingCount', 25)
            ->assertSee('event-24')->assertSee($organizations[0]->name)->assertSee($organizations[1]->name)
            ->assertViewHas('days', fn ($days) => $days[0]->format('Y-m-d') === '2026-09-28'
                && end($days)->format('Y-m-d') === '2026-11-01');
    }

    public function test_client_calendar_and_filters_cannot_expose_other_locations(): void
    {
        $organizations = Organization::query()->take(2)->get();
        $client = $this->account('client');
        $client->organizations()->attach($organizations[0]);
        $this->booking($organizations[0], 'allowed-event', '2026-10-12 09:00:00');
        $private = $this->booking($organizations[1], 'private-event', '2026-10-12 09:00:00');

        $this->actingAs($client)->get('/bookings?month=2026-10')
            ->assertOk()->assertSee('allowed-event')->assertDontSee('private-event')
            ->assertDontSee($organizations[1]->name);
        $this->get('/bookings?month=2026-10&organization_id='.$organizations[1]->id)
            ->assertOk()->assertViewHas('bookingCount', 0)->assertDontSee('private-event');
        $this->get('/bookings/'.$private->id)->assertForbidden();
    }

    public function test_calendar_includes_grid_boundaries_and_applies_status_filters(): void
    {
        $organization = Organization::query()->first();
        $this->booking($organization, 'first-boundary', '2026-09-28 00:00:00');
        $this->booking($organization, 'last-boundary', '2026-11-01 23:59:59');
        $this->booking($organization, 'outside-grid', '2026-11-02 00:00:00');
        $this->booking($organization, 'cancelled-event', '2026-10-12 09:00:00', 'cancelled');

        $this->actingAs($this->account('admin'))->get('/bookings?month=2026-10&status=confirmed')
            ->assertOk()->assertViewHas('bookingCount', 2)
            ->assertSee('first-boundary')->assertSee('last-boundary')
            ->assertDontSee('outside-grid')->assertDontSee('cancelled-event');
    }

    public function test_empty_month_defaults_and_invalid_month_is_rejected(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 11));
        $this->actingAs($this->account('admin'))->get('/bookings?month=')
            ->assertOk()->assertViewHas('month', fn ($month) => $month->format('Y-m') === '2026-10');
        $this->getJson('/bookings?month=2026-13')->assertUnprocessable();
    }

    public function test_list_view_remains_paginated_and_guests_must_sign_in(): void
    {
        $this->get('/bookings')->assertRedirect('/login');
        $organization = Organization::query()->first();
        for ($index = 0; $index < 25; $index++) {
            $this->booking($organization, 'list-'.$index, '2026-10-12 09:00:00');
        }

        $this->actingAs($this->account('admin'))->get('/bookings?view=list')
            ->assertOk()->assertViewIs('bookings.index')
            ->assertViewHas('bookings', fn ($bookings) => $bookings->count() === 20 && $bookings->total() === 25);
    }
}
