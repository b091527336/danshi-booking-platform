@extends('layouts.admin')
@section('title', '新增據點｜DBP')
@section('content')
<div class="mb-4"><a class="small text-decoration-none" href="{{ route('organizations.index') }}">← 返回據點管理</a><h1 class="h3 mt-2">新增據點</h1><p class="text-secondary mb-0">建立 DBP 據點並綁定 Tablesit Organization UID</p></div>
<div class="card stat-card" style="max-width:820px"><div class="card-body p-4 p-lg-5">
    <form method="POST" action="{{ route('organizations.store') }}">@csrf
        <div class="mb-3"><label class="form-label">據點名稱</label><input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label">系統代碼</label><input class="form-control @error('slug') is-invalid @enderror" name="slug" value="{{ old('slug') }}" placeholder="例如 anasa-taipei" required>@error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">時區</label><input class="form-control @error('timezone') is-invalid @enderror" name="timezone" value="{{ old('timezone', 'Asia/Taipei') }}" required>@error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label">外部服務</label><input class="form-control @error('external_provider') is-invalid @enderror" name="external_provider" value="{{ old('external_provider', 'tablesit') }}" required>@error('external_provider')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Tablesit Organization UID</label><input class="form-control @error('external_id') is-invalid @enderror" name="external_id" value="{{ old('external_id') }}" required>@error('external_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
        <div class="form-check form-switch mb-4"><input type="hidden" name="is_active" value="0"><input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', true))><label class="form-check-label" for="is_active">啟用此據點</label></div>
        <button class="btn btn-primary px-4" type="submit">新增據點</button>
    </form>
</div></div>
@endsection
