@extends('layouts.admin')
@section('title', '編輯據點｜DBP')
@section('content')
<div class="mb-4"><a class="small text-decoration-none" href="{{ route('organizations.show', $organization) }}">← 返回據點明細</a><h1 class="h3 mt-2">編輯據點</h1></div>
<div class="card stat-card" style="max-width:820px"><div class="card-body p-4 p-lg-5">
    <form method="POST" action="{{ route('organizations.update', $organization) }}">@csrf @method('PUT')
        <div class="mb-3"><label class="form-label">據點名稱</label><input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $organization->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label">系統代碼</label><input class="form-control @error('slug') is-invalid @enderror" name="slug" value="{{ old('slug', $organization->slug) }}" required>@error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">時區</label><input class="form-control @error('timezone') is-invalid @enderror" name="timezone" value="{{ old('timezone', $organization->timezone) }}" required>@error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label">外部服務</label><input class="form-control @error('external_provider') is-invalid @enderror" name="external_provider" value="{{ old('external_provider', $organization->external_provider) }}" required>@error('external_provider')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">TableSit／外部據點 ID</label><input class="form-control @error('external_id') is-invalid @enderror" name="external_id" value="{{ old('external_id', $organization->external_id) }}">@error('external_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
        <div class="form-check form-switch mb-4"><input type="hidden" name="is_active" value="0"><input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $organization->is_active))><label class="form-check-label" for="is_active">啟用此據點</label></div>
        <button class="btn btn-primary px-4" type="submit">儲存變更</button>
    </form>
</div></div>
@endsection
