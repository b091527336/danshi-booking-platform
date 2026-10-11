<!doctype html><html lang="zh-Hant"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>設定密碼｜ANASA 管理後台</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100"><div class="card shadow border-0" style="width:min(440px,calc(100% - 2rem))"><div class="card-body p-4 p-md-5">
<h1 class="h3">ANASA 管理後台</h1><p class="text-secondary">帳號：{{ $user->username }}</p>
<form method="POST" action="{{ route('client.activate.store', ['user' => $user, 'token' => $token]) }}">@csrf
<label class="form-label" for="password">設定密碼</label><input class="form-control mb-3" id="password" name="password" type="password" minlength="8" required>
<label class="form-label" for="password_confirmation">再次輸入密碼</label><input class="form-control mb-4" id="password_confirmation" name="password_confirmation" type="password" minlength="8" required>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<button class="btn btn-warning btn-lg w-100" type="submit">完成密碼設定</button></form>
</div></div></body></html>
