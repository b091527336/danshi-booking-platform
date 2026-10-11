<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DBP 後台')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --dbp-ink:#172b40; --dbp-teal:#147d79; --dbp-line:#e4eaf0; }
        body { background:#f3f6fa; color:var(--dbp-ink); font-family:Inter,"Noto Sans TC",system-ui,sans-serif; }
        .dbp-sidebar { width:256px; flex-shrink:0; min-height:100vh; background:linear-gradient(160deg,#172e43,#0e1c2e); }
        .dbp-brand { color:#fff; letter-spacing:.03em; padding-bottom:24px; border-bottom:1px solid #ffffff18; }
        .dbp-brand:before { content:"D"; display:inline-grid; place-items:center; width:36px; height:36px; border-radius:11px; background:#28b7a6; margin-right:10px; }
        .dbp-nav-link { color:#bdcbd9; border-radius:10px; padding:13px 16px; font-weight:500; }
        .dbp-nav-link:hover,.dbp-nav-link.active { color:#fff; background:#ffffff12; }
        .dbp-nav-link.active { box-shadow:inset 3px 0 #4ed8c5; }
        section.flex-grow-1 { min-width:0; }
        header { min-height:76px; }
        h1,h2,h3 { letter-spacing:-.025em; font-weight:700; }
        .stat-card { border:1px solid var(--dbp-line); border-radius:18px; overflow:hidden; box-shadow:0 6px 24px #182e4306; }
        .stat-card .card-header { padding-bottom:20px; }
        .btn { border-radius:9px; font-weight:600; padding:.6rem 1rem; }
        .btn-primary { background:var(--dbp-teal); border-color:var(--dbp-teal); }
        .btn-primary:hover { background:#106461; border-color:#106461; }
        .form-control,.form-select { border-color:#dbe3eb; border-radius:9px; min-height:43px; }
        .table > :not(caption) > * > * { padding:1rem; border-color:#edf1f5; }
        .table-light { --bs-table-bg:#f8fafc; --bs-table-color:#728093; font-size:.8rem; }
        .badge { border-radius:7px; padding:.5em .7em; font-weight:600; }
        .dashboard-hero { background:linear-gradient(115deg,#143c51,#157b77); color:#fff; padding:30px; border-radius:20px; }
        .calendar-grid { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); }
        .calendar-weekday { padding:14px; text-align:center; font-size:.8rem; color:#78869a; background:#f8fafc; }
        .calendar-day { min-height:155px; padding:10px; border-top:1px solid var(--dbp-line); border-right:1px solid var(--dbp-line); background:white; }
        .calendar-day.muted { background:#f9fbfd; color:#a5afbc; }
        .calendar-day.today { background:#effaf8; }
        .calendar-number { display:inline-grid; place-items:center; height:28px; width:28px; margin-bottom:7px; font-size:.85rem; font-weight:600; }
        .today .calendar-number { background:var(--dbp-teal); color:white; border-radius:50%; }
        .calendar-event { display:block; color:var(--dbp-ink); text-decoration:none; padding:7px; border-radius:7px; margin-bottom:6px; font-size:.75rem; line-height:1.5; border-left:3px solid var(--event-color); background:color-mix(in srgb,var(--event-color) 10%,white); overflow-wrap:anywhere; }
        .calendar-event:hover { box-shadow:0 2px 8px #172b4020; }
        .calendar-event.cancelled { opacity:.55; text-decoration:line-through; }
        .legend-dot { display:inline-block; width:9px; height:9px; border-radius:50%; background:var(--event-color); margin-right:6px; }
        @media(min-width:992px) { .dbp-sidebar { position:sticky; top:0; height:100vh; } }
        @media(max-width:991.98px) { .dbp-sidebar { width:100%; min-height:auto; } .dbp-sidebar nav { flex-direction:row!important; flex-wrap:wrap; gap:4px!important; } .dbp-brand { margin-bottom:12px!important; padding-bottom:12px; } }
        @media(max-width:767px) { .calendar-scroll { overflow-x:auto; } .calendar-grid { min-width:840px; } .dashboard-hero { padding:22px; } }
    </style>
</head>
<body>
<div class="d-lg-flex">
    <aside class="dbp-sidebar p-4">
        <div class="dbp-brand fw-bold fs-5 mb-4">{{ auth()->user()->isAdmin() ? '丹媞創網 DBP' : 'ANASA 管理後台' }}</div>
        <nav class="nav flex-column gap-2">
            <a class="nav-link dbp-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">營運總覽</a>
            <a class="nav-link dbp-nav-link {{ request()->routeIs('bookings.*') ? 'active' : '' }}" href="{{ route('bookings.index') }}">共用日曆與預約</a>
            <a class="nav-link dbp-nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">客戶管理</a>
            <a class="nav-link dbp-nav-link {{ request()->routeIs('organizations.*') ? 'active' : '' }}" href="{{ route('organizations.index') }}">據點管理</a>
            <a class="nav-link dbp-nav-link {{ request()->routeIs('sync.*') ? 'active' : '' }}" href="{{ route('sync.index') }}">同步中心</a>
            @if(auth()->user()->isAdmin())
                <a class="nav-link dbp-nav-link {{ request()->routeIs('client-access.*') ? 'active' : '' }}" href="{{ route('client-access.index') }}">客戶帳號</a>
            @endif
        </nav>
    </aside>
    <section class="flex-grow-1">
        <header class="bg-white border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
            <div class="fw-semibold">預約營運中心 <span class="text-secondary small ms-2">DBP</span></div>
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
