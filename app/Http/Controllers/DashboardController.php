<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Organization;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(\Illuminate\Http\Request $request): View
    {
        $organizationIds = $request->user()->organizationIds();
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        return view('dashboard', [
            'organizationCount' => Organization::whereIn('id', $organizationIds)->where('is_active', true)->count(),
            'customerCount' => Customer::whereHas('bookings', fn ($query) => $query->whereIn('organization_id', $organizationIds))->count(),
            'bookingCount' => Booking::whereIn('organization_id', $organizationIds)->count(),
            'todayBookingCount' => Booking::whereIn('organization_id', $organizationIds)->whereBetween('starts_at', [$todayStart, $todayEnd])->count(),
            'recentBookings' => Booking::query()
                ->with(['organization', 'customer'])
                ->whereIn('organization_id', $organizationIds)
                ->latest('starts_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
