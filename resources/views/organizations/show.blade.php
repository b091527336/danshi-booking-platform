@extends('layouts.admin')
@section('title', '據點明細｜DBP')
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><a class="small text-decoration-none" href="{{ route('organizations.index') }}">← 返回據點列表</a><h1 class="h3 mt-2 mb-1">{{ $organization->name }}</h1><p class="text-secondary mb-0">{{ $organization->external_provider }}／{{ $organization->external_id ?? '尚未綁定' }}</p></div>
    <a class="btn btn-primary" href="{{ route('organizations.edit', $organization) }}">編輯據點</a>
</div>
<div class="row g-4">
    <div class="col-12 col-xl-4"><div class="card stat-card"><div class="card-body p-4">
        <h2 class="h5 mb-4">據點設定</h2>
        <div class="text-secondary small">狀態</div><div class="mb-3"><span class="badge text-bg-{{ $organization->is_active ? 'success' : 'secondary' }}">{{ $organization->is_active ? '啟用' : '停用' }}</span></div>
        <div class="text-secondary small">系統代碼</div><div class="mb-3"><code>{{ $organization->slug }}</code></div>
        <div class="text-secondary small">時區</div><div class="mb-3">{{ $organization->timezone }}</div>
        <div class="text-secondary small">累計預約</div><div>{{ number_format($organization->bookings_count) }} 筆</div>
    </div></div></div>
    <div class="col-12 col-xl-8"><div class="card stat-card"><div class="card-header bg-white border-0 px-4 pt-4"><h2 class="h5">最近預約</h2></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th class="ps-4">時間</th><th>客戶</th><th>服務</th><th>狀態</th><th></th></tr></thead><tbody>
        @forelse($organization->bookings as $booking)
            <tr><td class="ps-4">{{ $booking->starts_at?->timezone(config('app.timezone'))->format('Y/m/d H:i') }}</td><td>{{ $booking->customer?->name ?? '—' }}</td><td>{{ $booking->service_name ?? '—' }}</td><td>{{ $booking->status }}</td><td class="pe-4 text-end"><a href="{{ route('bookings.show', $booking) }}">查看</a></td></tr>
        @empty<tr><td colspan="5" class="text-center text-secondary py-5">尚無預約紀錄</td></tr>@endforelse
        </tbody></table></div>
    </div></div>
</div>
@endsection
