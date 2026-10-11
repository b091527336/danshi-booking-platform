@extends('layouts.admin')
@section('title', '共用日曆｜DBP')
@section('content')
@php
    $colors = ['#167c80','#6754ac','#c78120','#3574b3','#bc5577','#598145'];
    $colorMap = [];
    foreach ($organizations as $index => $organization) { $colorMap[$organization->id] = $colors[$index % count($colors)]; }
    $monthQuery = request()->except(['month', 'page']);
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><div class="text-secondary small mb-1">BOOKING CALENDAR</div><h1 class="h3 mb-1">共用預約日曆</h1><p class="text-secondary mb-0">所有授權據點集中排程 · 台北時間</p></div>
    <a class="btn btn-outline-secondary" href="{{ route('bookings.index', array_merge(request()->except('page'), ['view'=>'list'])) }}">預約清單</a>
</div>
<div class="card stat-card mb-4"><div class="card-body p-4">
    <form method="GET" action="{{ route('bookings.index') }}" class="row g-3 align-items-end">
        <input type="hidden" name="view" value="calendar"><input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
        <div class="col-12 col-md-4"><label for="organization_id" class="form-label">據點</label><select class="form-select" name="organization_id" id="organization_id"><option value="">全部據點 · 共用日曆</option>@foreach($organizations as $organization)<option value="{{ $organization->id }}" @selected((string)request('organization_id')===(string)$organization->id)>{{ $organization->name }}</option>@endforeach</select></div>
        <div class="col-12 col-md-3"><label for="status" class="form-label">預約狀態</label><select class="form-select" name="status" id="status"><option value="">全部狀態</option>@foreach($statuses as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-12 col-md-3"><label for="keyword" class="form-label">搜尋客戶或服務</label><input class="form-control" id="keyword" name="keyword" value="{{ request('keyword') }}" placeholder="姓名、電話、服務項目"></div>
        <div class="col-12 col-md-2"><button class="btn btn-primary w-100">套用篩選</button></div>
    </form>
</div></div>
<div class="card stat-card">
    <div class="p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><h2 class="h4 mb-1">{{ $month->format('Y 年 n 月') }}</h2><span class="text-secondary small">目前日曆範圍 {{ $bookingCount }} 筆預約</span></div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" aria-label="上個月" href="{{ route('bookings.index', array_merge($monthQuery, ['view'=>'calendar','month'=>$month->subMonth()->format('Y-m')])) }}">←</a>
            <a class="btn btn-outline-secondary" href="{{ route('bookings.index', array_merge($monthQuery, ['view'=>'calendar','month'=>now()->format('Y-m')])) }}">本月</a>
            <a class="btn btn-outline-secondary" aria-label="下個月" href="{{ route('bookings.index', array_merge($monthQuery, ['view'=>'calendar','month'=>$month->addMonth()->format('Y-m')])) }}">→</a>
        </div>
    </div>
    <div class="px-4 pb-3 d-flex flex-wrap gap-3 small">@foreach($organizations as $organization)<span style="--event-color:{{ $colorMap[$organization->id] }}"><span class="legend-dot"></span>{{ $organization->name }}</span>@endforeach</div>
    <div class="calendar-scroll"><div class="calendar-grid">
        @foreach(['一','二','三','四','五','六','日'] as $weekday)<div class="calendar-weekday">星期{{ $weekday }}</div>@endforeach
        @foreach($days as $day)
            <div class="calendar-day {{ $day->month !== $month->month ? 'muted' : '' }} {{ $day->isToday() ? 'today' : '' }}">
                <time class="calendar-number" datetime="{{ $day->format('Y-m-d') }}">{{ $day->day }}</time>
                @foreach($bookingsByDay->get($day->format('Y-m-d'), collect()) as $booking)
                    <a class="calendar-event {{ $booking->status==='cancelled' ? 'cancelled' : '' }}" style="--event-color:{{ $colorMap[$booking->organization_id] ?? '#167c80' }}" href="{{ route('bookings.show', $booking) }}">
                        <strong>{{ $booking->starts_at->timezone(config('app.timezone'))->format('H:i') }} · {{ $booking->customer?->name ?? '未提供姓名' }}</strong><br>
                        {{ $booking->organization?->name ?? '未提供據點' }}<br>{{ $booking->service_name ?? '未提供服務' }}<br><span>{{ $statuses[$booking->status] ?? $booking->status }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </div></div>
    @if($bookingCount===0)<div class="p-4 text-center text-secondary border-top">這個月尚無符合條件的預約。可切換月份或調整篩選。</div>@endif
</div>
<p class="text-secondary small mt-3">點選預約可查看詳情。日曆顯示各據點同步至 DBP 的預約紀錄。</p>
@endsection
