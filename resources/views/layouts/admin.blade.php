<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DBP 後台')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; color: #263238; }
        .dbp-sidebar { width: 250px; min-height: 100vh; background: linear-gradient(180deg, #18243a, #0f1726); }
        .dbp-brand { color: #d8b76b; letter-spacing: .08em; }
        .dbp-nav-link { color: rgba(255,255,255,.72); border-radius: .65rem; }
        .dbp-nav-link:hover, .dbp-nav-link.active { color: #fff; background: rgba(216,183,107,.18); }
        .stat-card { border: 0; border-radius: 1rem; box-shadow: 0 .4rem 1.4rem rgba(15,23,38,.08); }
        @media (max-width: 991.98px) { .dbp-sidebar { width: 100%; min-height: auto; } }
    </style>
</head>
<body>
<div class="d-lg-flex">
    <aside class="dbp-sidebar p-4">
        <div class="dbp-brand fw-bold fs-5 mb-4">丹媞創網 DBP</div>
        <nav class="nav flex-column gap-2">
            <a class="nav-link dbp-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
            <a class="nav-link dbp-nav-link {{ request()->routeIs('bookings.*') ? 'active' : '' }}" href="{{ route('bookings.index') }}">預約管理</a>
            <a class="nav-link dbp-nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">客戶管理</a>
            <a class="nav-link dbp-nav-link {{ request()->routeIs('organizations.*') ? 'active' : '' }}" href="{{ route('organizations.index') }}">據點管理</a>
            <a class="nav-link dbp-nav-link {{ request()->routeIs('sync.*') ? 'active' : '' }}" href="{{ route('sync.index') }}">同步中心</a>
        </nav>
    </aside>
    <section class="flex-grow-1">
        <header class="bg-white border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
            <div class="fw-semibold">Booking Platform</div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary small">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="btn btn-sm btn-outline-secondary" type="submit">登出</button>
                </form>
            </div>
        </header>
        <main class="p-4 p-lg-5">@yield('content')</main>
    </section>
</div>
</body>
</html>
