@extends('layouts.admin')
@section('title', '同步中心｜DBP')
@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">TableSit 同步中心</h1>
    <p class="text-secondary mb-0">查看各據點串接狀態與執行紀錄</p>
</div>

@foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $style)
    @if(session($key))<div class="alert alert-{{ $style }}">{{ session($key) }}</div>@endif
@endforeach

<section class="card stat-card p-4 mb-4">
    <h2 class="h5">Tablesit Webhook</h2>
    <p>{{ $webhookConfigured ? '簽章密鑰已設定；請以測試事件及實際預約確認送達與處理結果。' : '尚未設定簽章密鑰，接收端保持關閉；現有每分鐘 API 同步繼續運作。' }}</p>
    <p>接收網址：<code>{{ url('/tablesit/webhook') }}</code></p>
    <p class="small text-secondary">測試事件不會建立預約。收到事件後先保存，再由每分鐘背景程序查詢最新狀態；失敗會重試，最多 12 次，之後標示失敗。</p>
    <div class="table-responsive"><table class="table"><thead><tr><th>事件</th><th>類型</th><th>狀態</th><th>重試</th></tr></thead><tbody>
    @forelse($webhookEvents as $event)
        <tr><td>{{ $event->event_id }}</td><td>{{ $event->event_type }}</td><td>{{ ['pending'=>'等待處理','processed'=>'已處理','test'=>'測試已收到','ignored'=>'已略過','failed'=>'處理失敗'][$event->status] ?? $event->status }}</td><td>{{ $event->attempts }}</td></tr>
    @empty
        <tr><td colspan="4">尚未收到 Webhook 事件。</td></tr>
    @endforelse
    </tbody></table></div>
</section>

<div class="row g-4 mb-5">
    @foreach($organizations as $organization)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between gap-3 mb-3">
                        <div>
                            <h2 class="h5 mb-1">{{ $organization->name }}</h2>
                            <code>{{ $organization->slug }}</code>
                        </div>
                        <span class="badge text-bg-{{ $organization->api_key_configured ? 'success' : 'secondary' }} align-self-start">
                            {{ $organization->api_key_configured ? 'API 已設定' : '尚缺 API Key' }}
                        </span>
                    </div>
                    <form method="POST" action="{{ route('sync.store', $organization) }}">
                        @csrf
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small">開始日期</label>
                                <input class="form-control form-control-sm" name="date_from" type="date" value="{{ now()->subDays(30)->toDateString() }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small">結束日期</label>
                                <input class="form-control form-control-sm" name="date_to" type="date" value="{{ now()->addYear()->toDateString() }}">
                            </div>
                        </div>
                        <button class="btn btn-primary w-100" type="submit" @disabled(! $organization->api_key_configured)>
                            立即同步此據點
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card stat-card">
    <div class="card-header bg-white border-0 px-4 pt-4">
        <h2 class="h5 mb-0">同步紀錄</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr><th class="ps-4">執行時間</th><th>據點</th><th>狀態</th><th>收到</th><th>新增</th><th>更新</th><th>失敗</th><th class="pe-4">錯誤摘要</th></tr>
            </thead>
            <tbody>
            @forelse($runs as $run)
                @php
                    $badge = match($run->status) {
                        'completed' => 'success',
                        'partial' => 'warning',
                        'failed' => 'danger',
                        default => 'secondary',
                    };
                    $label = match($run->status) {
                        'completed' => '完成',
                        'partial' => '部分完成',
                        'failed' => '失敗',
                        'running' => '執行中',
                        default => $run->status,
                    };
                @endphp
                <tr>
                    <td class="ps-4">{{ $run->started_at?->timezone(config('app.timezone'))->format('Y/m/d H:i:s') }}</td>
                    <td>{{ $run->organization?->name ?? '全部／已移除' }}</td>
                    <td><span class="badge text-bg-{{ $badge }}">{{ $label }}</span></td>
                    <td>{{ $run->received_count }}</td>
                    <td>{{ $run->created_count }}</td>
                    <td>{{ $run->updated_count }}</td>
                    <td>{{ $run->failed_count }}</td>
                    <td class="pe-4 text-danger small" style="max-width:320px">{{ Illuminate\Support\Str::limit($run->error_message, 100) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-secondary py-5">尚無同步紀錄</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($runs->hasPages())<div class="card-footer bg-white px-4 py-3">{{ $runs->links() }}</div>@endif
</div>
@endsection
