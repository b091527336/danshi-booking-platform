<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'status' => ['nullable', 'in:pending,confirmed,completed,cancelled,no_show'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $bookings = Booking::query()
            ->with(['organization', 'customer'])
            ->when($filters['keyword'] ?? null, function (Builder $query, string $keyword) {
                $query->where(function (Builder $query) use ($keyword) {
                    $query
                        ->where('external_id', 'like', "%{$keyword}%")
                        ->orWhere('service_name', 'like', "%{$keyword}%")
                        ->orWhereHas('customer', function (Builder $customerQuery) use ($keyword) {
                            $customerQuery
                                ->where('name', 'like', "%{$keyword}%")
                                ->orWhere('phone', 'like', "%{$keyword}%")
                                ->orWhere('email', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($filters['organization_id'] ?? null, fn (Builder $query, $organizationId) =>
                $query->where('organization_id', $organizationId))
            ->when($filters['status'] ?? null, fn (Builder $query, $status) =>
                $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn (Builder $query, $dateFrom) =>
                $query->where('starts_at', '>=', now()->parse($dateFrom)->startOfDay()->utc()))
            ->when($filters['date_to'] ?? null, fn (Builder $query, $dateTo) =>
                $query->where('starts_at', '<=', now()->parse($dateTo)->endOfDay()->utc()))
            ->orderByDesc('starts_at')
            ->paginate(20)
            ->withQueryString();

        return view('bookings.index', [
            'bookings' => $bookings,
            'organizations' => Organization::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'statuses' => $this->statuses(),
        ]);
    }

    public function show(Booking $booking): View
    {
        $booking->load(['organization', 'customer']);

        return view('bookings.show', [
            'booking' => $booking,
            'statuses' => $this->statuses(),
        ]);
    }

    private function statuses(): array
    {
        return [
            'pending' => '待確認',
            'confirmed' => '已確認',
            'completed' => '已完成',
            'cancelled' => '已取消',
            'no_show' => '未出席',
        ];
    }
}
