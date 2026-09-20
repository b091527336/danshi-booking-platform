<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>登入｜丹媞創網 DBP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; background: linear-gradient(135deg, #111b2c, #263956); }
        .login-card { width: min(430px, calc(100% - 2rem)); border: 0; border-radius: 1.25rem; box-shadow: 0 1.5rem 4rem rgba(0,0,0,.28); }
        .brand { color: #ad8330; letter-spacing: .08em; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">
<div class="card login-card">
    <div class="card-body p-4 p-md-5">
        <div class="brand fw-bold mb-2">丹媞創網 DBP</div>
        <h1 class="h3 mb-1">管理者登入</h1>
        <p class="text-secondary mb-4">集中管理所有據點與預約資料</p>

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="email">電子郵件</label>
                <input class="form-control form-control-lg @error('email') is-invalid @enderror"
                       id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">密碼</label>
                <input class="form-control form-control-lg" id="password" name="password" type="password" required>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1">
                <label class="form-check-label" for="remember">保持登入</label>
            </div>
            <button class="btn btn-warning btn-lg w-100" type="submit">登入後台</button>
        </form>
    </div>
</div>
</body>
</html>
