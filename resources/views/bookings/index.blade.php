@extends('layouts.admin')

@section('title', '預約管理｜DBP')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">預約管理</h1>
        <p class="text-secondary mb-0">集中查詢所有據點的預約紀錄</p>
    </div>
    <span class="badge rounded-pill text-bg-light border px-3 py-2">共 {{ number_format($bookings->total()) }} 筆</span>
</div>

<div class="card stat-card mb-4">
    <div class="card-body p-4">
        <form method="GET" action="{{ route('bookings.index') }}">
            <div class="row g-3">
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="keyword">關鍵字</label>
                    <input class="form-control" id="keyword" name="keyword" value="{{ request('keyword') }}"
                           placeholder="客戶、電話、Email、預約編號">
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label" for="organization_id">據點</label>
                    <select class="form-select" id="organization_id" name="organization_id">
                        <option value="">全部據點</option>
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization->id }}" @selected((string) request('organization_id') === (string) $organization->id)>
                                {{ $organization->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label" for="status">狀態</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">全部狀態</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="date_from">開始日期</label>
                    <input class="form-control" id="date_from" name="date_from" type="date" value="{{ request('date_from') }}">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="date_to">結束日期</label>
                    <input class="form-control" id="date_to" name="date_to" type="date" value="{{ request('date_to') }}">
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-primary px-4" type="submit">套用篩選</button>
                <a class="btn btn-outline-secondary" href="{{ route('bookings.index') }}">清除</a>
            </div>
        </form>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">篩選條件有誤，請重新確認日期或選項。</div>
@endif

<div class="card stat-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th class="ps-4">預約時間</th>
                <th>客戶</th>
                <th>據點</th>
                <th>服務</th>
                <th>人數</th>
                <th>狀態</th>
                <th class="text-end pe-4">操作</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($bookings as $booking)
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
                <tr>
                    <td class="ps-4">
                        <div class="fw-semibold">{{ $booking->starts_at?->timezone(config('app.timezone'))->format('Y/m/d') }}</div>
                        <div class="text-secondary small">{{ $booking->starts_at?->timezone(config('app.timezone'))->format('H:i') }}</div>
                    </td>
                    <td>
                        <div>{{ $booking->customer?->name ?? '未提供' }}</div>
                        <div class="text-secondary small">{{ $booking->customer?->phone }}</div>
                    </td>
                    <td>{{ $booking->organization?->name ?? '—' }}</td>
                    <td>{{ $booking->service_name ?? '—' }}</td>
                    <td>{{ $booking->party_size ?? '—' }}</td>
                    <td><span class="badge text-bg-{{ $badge }}">{{ $statuses[$booking->status] ?? $booking->status }}</span></td>
                    <td class="text-end pe-4">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('bookings.show', $booking) }}">查看</a>
                    </td>
                </tr>
            @empty
                <tr><td class="text-center text-secondary py-5" colspan="7">目前沒有符合條件的預約</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($bookings->hasPages())
        <div class="card-footer bg-white px-4 py-3">{{ $bookings->links() }}</div>
    @endif
</div>
@endsection
