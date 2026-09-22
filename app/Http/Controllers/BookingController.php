<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $bookings = $this->filteredBookings($filters)
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

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $filename = 'DBP-bookings-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($filters) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['預約編號', '預約時間', '結束時間', '客戶姓名', '電話', 'Email', '據點', '服務', '人數', '狀態', '來源']);

            $this->filteredBookings($filters)
                ->orderBy('starts_at')
                ->chunkById(200, function ($bookings) use ($output) {
                    foreach ($bookings as $booking) {
                        fputcsv($output, array_map($this->csvValue(...), [
                            $booking->external_id,
                            $booking->starts_at?->format('Y-m-d H:i'),
                            $booking->ends_at?->format('Y-m-d H:i'),
                            $booking->customer?->name,
                            $booking->customer?->phone,
                            $booking->customer?->email,
                            $booking->organization?->name,
                            $booking->service_name,
                            $booking->party_size,
                            $this->statuses()[$booking->status] ?? $booking->status,
                            $booking->source,
                        ]));
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function show(Booking $booking): View
    {
        $booking->load(['organization', 'customer']);

        return view('bookings.show', [
            'booking' => $booking,
            'statuses' => $this->statuses(),
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'status' => ['nullable', 'in:pending,confirmed,completed,cancelled,no_show'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);
    }

    private function filteredBookings(array $filters): Builder
    {
        return Booking::query()
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
                $query->where('starts_at', '>=', $this->localDateBoundary($dateFrom, false)))
            ->when($filters['date_to'] ?? null, fn (Builder $query, $dateTo) =>
                $query->where('starts_at', '<=', $this->localDateBoundary($dateTo, true)));
    }

    private function csvValue(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@]/', $value) ? "'{$value}" : $value;
    }

    private function localDateBoundary(string $date, bool $endOfDay): CarbonImmutable
    {
        $value = CarbonImmutable::createFromFormat('Y-m-d', $date, config('app.timezone'));

        return $endOfDay ? $value->endOfDay() : $value->startOfDay();
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
