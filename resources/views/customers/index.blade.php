@extends('layouts.admin')
@section('title', '客戶管理｜DBP')
@section('content')
<div class="d-flex justify-content-between align-items-end mb-4">
    <div><h1 class="h3 mb-1">客戶管理</h1><p class="text-secondary mb-0">查詢客戶與歷史預約</p></div>
    <span class="badge rounded-pill text-bg-light border px-3 py-2">共 {{ number_format($customers->total()) }} 位</span>
</div>
<div class="card stat-card mb-4">
    <div class="card-body p-4">
        <form class="row g-2" method="GET">
            <div class="col-12 col-md-8 col-xl-5">
                <input class="form-control" name="keyword" value="{{ request('keyword') }}" placeholder="搜尋姓名、電話或 Email">
            </div>
            <div class="col-auto"><button class="btn btn-primary" type="submit">搜尋</button></div>
            <div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('customers.index') }}">清除</a></div>
        </form>
    </div>
</div>
<div class="card stat-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th class="ps-4">姓名</th><th>電話</th><th>Email</th><th>預約次數</th><th>最近更新</th><th class="text-end pe-4">操作</th></tr></thead>
            <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td class="ps-4 fw-semibold">{{ $customer->name }}</td>
                    <td>{{ $customer->phone ?? '—' }}</td>
                    <td>{{ $customer->email ?? '—' }}</td>
                    <td>{{ number_format($customer->bookings_count) }}</td>
                    <td>{{ $customer->updated_at?->timezone(config('app.timezone'))->format('Y/m/d H:i') }}</td>
                    <td class="text-end pe-4"><a class="btn btn-sm btn-outline-primary" href="{{ route('customers.show', $customer) }}">查看</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-secondary py-5">目前沒有符合條件的客戶</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())<div class="card-footer bg-white px-4 py-3">{{ $customers->links() }}</div>@endif
</div>
@endsection
