@extends('layouts.admin')
@section('title', '編輯客戶｜DBP')
@section('content')
<div class="mb-4"><a class="small text-decoration-none" href="{{ route('customers.show', $customer) }}">← 返回客戶明細</a><h1 class="h3 mt-2">編輯客戶</h1></div>
<div class="card stat-card" style="max-width:760px"><div class="card-body p-4 p-lg-5">
    <form method="POST" action="{{ route('customers.update', $customer) }}">@csrf @method('PUT')
        <div class="mb-3"><label class="form-label">姓名</label><input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $customer->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label">電話</label><input class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $customer->phone) }}">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Email</label><input class="form-control @error('email') is-invalid @enderror" name="email" type="email" value="{{ old('email', $customer->email) }}">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
        <div class="mb-4"><label class="form-label">備註</label><textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="5">{{ old('notes', $customer->notes) }}</textarea>@error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <button class="btn btn-primary px-4" type="submit">儲存變更</button>
    </form>
</div></div>
@endsection
