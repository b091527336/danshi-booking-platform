@extends('layouts.admin')

@section('title', '營運總覽｜DBP')

@section('content')
<div class="dashboard-hero mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div><div class="small mb-2 opacity-75">DANSHI BOOKING PLATFORM</div><h1 class="h3 mb-2">每一筆預約，都在掌握之中</h1><p class="mb-0 opacity-75">多據點集中管理 · 台北時間 {{ now()->format('Y/m/d') }}</p></div>
    <a class="btn btn-light" href="{{ route('bookings.index') }}">開啟共用日曆 →</a>
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
                        <td><span class="badge text-bg-secondary">{{ ['confirmed'=>'已確認','pending'=>'待確認','completed'=>'已完成','cancelled'=>'已取消','no_show'=>'未出席'][$booking->status] ?? $booking->status }}</span></td>
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
