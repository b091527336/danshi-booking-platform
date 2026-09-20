@extends('layouts.admin')
@section('title', '客戶明細｜DBP')
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><a class="small text-decoration-none" href="{{ route('customers.index') }}">← 返回客戶列表</a><h1 class="h3 mt-2 mb-1">{{ $customer->name }}</h1><p class="text-secondary mb-0">累計 {{ number_format($customer->bookings_count) }} 筆預約</p></div>
    <a class="btn btn-primary" href="{{ route('customers.edit', $customer) }}">編輯客戶</a>
</div>
<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card stat-card"><div class="card-body p-4">
            <h2 class="h5 mb-4">聯絡資料</h2>
            <div class="text-secondary small">電話</div><div class="mb-3">{{ $customer->phone ?? '—' }}</div>
            <div class="text-secondary small">Email</div><div class="mb-3">{{ $customer->email ?? '—' }}</div>
            <div class="text-secondary small">備註</div><div>{!! nl2br(e($customer->notes ?? '—')) !!}</div>
        </div></div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="card stat-card"><div class="card-header bg-white border-0 px-4 pt-4"><h2 class="h5">最近預約</h2></div>
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th class="ps-4">時間</th><th>據點</th><th>服務</th><th>狀態</th><th></th></tr></thead>
                <tbody>
                @forelse($customer->bookings as $booking)
                    <tr><td class="ps-4">{{ $booking->starts_at?->timezone(config('app.timezone'))->format('Y/m/d H:i') }}</td><td>{{ $booking->organization?->name ?? '—' }}</td><td>{{ $booking->service_name ?? '—' }}</td><td>{{ $booking->status }}</td><td class="pe-4 text-end"><a href="{{ route('bookings.show', $booking) }}">查看</a></td></tr>
                @empty<tr><td colspan="5" class="text-center text-secondary py-5">尚無預約紀錄</td></tr>@endforelse
                </tbody>
            </table></div>
        </div>
    </div>
</div>
@endsection
