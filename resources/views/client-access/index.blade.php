@extends('layouts.admin')
@section('title', '客戶帳號｜DBP')
@section('content')
<h1 class="h3 mb-4">客戶帳號</h1>
<div class="card stat-card"><div class="card-body">
    @foreach($clients as $client)
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 py-3 border-bottom">
            <div><strong>{{ $client->name }}</strong><div class="text-secondary">帳號：{{ $client->username }}｜{{ $client->organizations_count }} 個據點</div></div>
            <form method="POST" action="{{ route('client-access.invite', $client) }}">@csrf
                <button class="btn btn-warning" type="submit">產生首次密碼設定連結</button>
            </form>
        </div>
    @endforeach
</div></div>
@endsection
