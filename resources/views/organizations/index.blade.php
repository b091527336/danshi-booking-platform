@extends('layouts.admin')
@section('title', '據點管理｜DBP')
@section('content')
<div class="d-flex justify-content-between align-items-end mb-4">
    <div><h1 class="h3 mb-1">據點管理</h1><p class="text-secondary mb-0">管理各地區據點與 TableSit 對應</p></div>
    <span class="badge rounded-pill text-bg-light border px-3 py-2">共 {{ number_format($organizations->total()) }} 個</span>
</div>
<div class="card stat-card mb-4"><div class="card-body p-4">
    <form class="row g-2" method="GET">
        <div class="col-12 col-md-6"><input class="form-control" name="keyword" value="{{ request('keyword') }}" placeholder="搜尋據點名稱、代碼或外部 ID"></div>
        <div class="col-12 col-md-3"><select class="form-select" name="active"><option value="">全部狀態</option><option value="1" @selected(request('active')==='1')>啟用</option><option value="0" @selected(request('active')==='0')>停用</option></select></div>
        <div class="col-auto"><button class="btn btn-primary" type="submit">套用</button></div>
        <div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('organizations.index') }}">清除</a></div>
    </form>
</div></div>
<div class="card stat-card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th class="ps-4">據點</th><th>系統代碼</th><th>TableSit ID</th><th>預約數</th><th>狀態</th><th class="text-end pe-4">操作</th></tr></thead>
    <tbody>
    @forelse($organizations as $organization)
        <tr><td class="ps-4 fw-semibold">{{ $organization->name }}</td><td><code>{{ $organization->slug }}</code></td><td>{{ $organization->external_id ?? '尚未綁定' }}</td><td>{{ number_format($organization->bookings_count) }}</td><td><span class="badge text-bg-{{ $organization->is_active ? 'success' : 'secondary' }}">{{ $organization->is_active ? '啟用' : '停用' }}</span></td><td class="text-end pe-4"><a class="btn btn-sm btn-outline-primary" href="{{ route('organizations.show', $organization) }}">查看</a></td></tr>
    @empty<tr><td colspan="6" class="text-center text-secondary py-5">目前沒有符合條件的據點</td></tr>@endforelse
    </tbody>
</table></div>@if($organizations->hasPages())<div class="card-footer bg-white px-4 py-3">{{ $organizations->links() }}</div>@endif</div>
@endsection
