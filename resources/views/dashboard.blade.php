@extends('layouts.admin')

@section('title', 'Dashboard｜DBP')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Dashboard</h1>
    <p class="text-secondary mb-0">預約平台即時概況</p>
</div>

<div class="row g-4 mb-5">
    @foreach ([
        ['label' => '啟用據點', 'value' => $organizationCount, 'color' => 'primary'],
        ['label' => '全部預約', 'value' => $bookingCount, 'color' => 'success'],
        ['label' => '今日預約', 'value' => $todayBookingCount, 'color' => 'warning'],
        ['label' => '客戶人數', 'value' => $customerCount, 'color' => 'info'],
    ] as $item)
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body p-4">
                    <div class="text-secondary small mb-2">{{ $item['label'] }}</div>
                    <div class="display-6 fw-bold text-{{ $item['color'] }}">{{ number_format($item['value']) }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card stat-card">
    <div class="card-header bg-white border-0 px-4 pt-4">
        <h2 class="h5 mb-0">近期預約</h2>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th class="ps-4">時間</th><th>據點</th><th>客戶</th><th>服務</th><th>狀態</th></tr>
                </thead>
                <tbody>
                @forelse ($recentBookings as $booking)
                    <tr>
                        <td class="ps-4">{{ $booking->starts_at?->format('Y/m/d H:i') }}</td>
                        <td>{{ $booking->organization?->name ?? '—' }}</td>
                        <td>{{ $booking->customer?->name ?? '—' }}</td>
                        <td>{{ $booking->service_name ?? '—' }}</td>
                        <td><span class="badge text-bg-secondary">{{ $booking->status }}</span></td>
                    </tr>
                @empty
                    <tr><td class="text-center text-secondary py-5" colspan="5">尚無預約資料</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
