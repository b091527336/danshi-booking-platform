@extends('layouts.admin')

@section('title', '預約明細｜DBP')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <a class="text-decoration-none small" href="{{ route('bookings.index') }}">← 返回預約列表</a>
        <h1 class="h3 mt-2 mb-1">預約明細</h1>
        <p class="text-secondary mb-0">外部編號：{{ $booking->external_id }}</p>
    </div>
    @php
        $badge = match ($booking->status) {
            'confirmed' => 'success',
            'pending' => 'warning',
            'completed' => 'primary',
            'cancelled' => 'secondary',
            'no_show' => 'danger',
            default => 'secondary',
        };
    @endphp
    <span class="badge fs-6 text-bg-{{ $badge }}">{{ $statuses[$booking->status] ?? $booking->status }}</span>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card stat-card h-100">
            <div class="card-header bg-white border-0 px-4 pt-4"><h2 class="h5 mb-0">預約資訊</h2></div>
            <div class="card-body p-4">
                <dl class="row mb-0 gy-3">
                    <dt class="col-sm-4 text-secondary">預約日期</dt>
                    <dd class="col-sm-8">{{ $booking->starts_at?->timezone(config('app.timezone'))->format('Y年m月d日 H:i') }}</dd>
                    <dt class="col-sm-4 text-secondary">結束時間</dt>
                    <dd class="col-sm-8">{{ $booking->ends_at?->timezone(config('app.timezone'))->format('Y年m月d日 H:i') ?? '—' }}</dd>
                    <dt class="col-sm-4 text-secondary">服務項目</dt>
                    <dd class="col-sm-8">{{ $booking->service_name ?? '—' }}</dd>
                    <dt class="col-sm-4 text-secondary">預約人數</dt>
                    <dd class="col-sm-8">{{ $booking->party_size ?? '—' }}</dd>
                    <dt class="col-sm-4 text-secondary">預約據點</dt>
                    <dd class="col-sm-8">{{ $booking->organization?->name ?? '—' }}</dd>
                    <dt class="col-sm-4 text-secondary">資料來源</dt>
                    <dd class="col-sm-8">{{ ucfirst($booking->external_provider) }}</dd>
                    <dt class="col-sm-4 text-secondary">最後同步</dt>
                    <dd class="col-sm-8">{{ $booking->synced_at?->timezone(config('app.timezone'))->format('Y/m/d H:i:s') ?? '尚未同步' }}</dd>
                    <dt class="col-sm-4 text-secondary">備註</dt>
                    <dd class="col-sm-8">{!! nl2br(e($booking->notes ?? '—')) !!}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card stat-card">
            <div class="card-header bg-white border-0 px-4 pt-4"><h2 class="h5 mb-0">客戶資料</h2></div>
            <div class="card-body p-4">
                <div class="text-secondary small mb-1">姓名</div>
                <div class="fw-semibold mb-3">{{ $booking->customer?->name ?? '未提供' }}</div>
                <div class="text-secondary small mb-1">電話</div>
                <div class="mb-3">{{ $booking->customer?->phone ?? '—' }}</div>
                <div class="text-secondary small mb-1">Email</div>
                <div>{{ $booking->customer?->email ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
