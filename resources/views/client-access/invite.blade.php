@extends('layouts.admin')
@section('title', '首次登入連結｜DBP')
@section('content')
<h1 class="h3 mb-3">首次密碼設定連結</h1>
<div class="alert alert-warning">此連結有效 7 天，只能完成一次密碼設定。請安全地交給 {{ $client->name }}。</div>
<div class="card stat-card"><div class="card-body">
    <label class="form-label">帳號</label><input class="form-control mb-3" readonly value="{{ $client->username }}">
    <label class="form-label">設定連結</label><textarea class="form-control" rows="4" readonly>{{ $activationUrl }}</textarea>
</div></div>
@endsection
