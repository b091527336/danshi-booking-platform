<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Organization;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        return view('dashboard', [
            'organizationCount' => Organization::where('is_active', true)->count(),
            'customerCount' => Customer::count(),
            'bookingCount' => Booking::count(),
            'todayBookingCount' => Booking::whereBetween('starts_at', [$todayStart, $todayEnd])->count(),
            'recentBookings' => Booking::query()
                ->with(['organization', 'customer'])
                ->latest('starts_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
