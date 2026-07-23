<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ shop_name() }}</title>
    @if(shop_logo_url())
        <link rel="icon" href="{{ shop_logo_url() }}" type="image/png">
        <link rel="apple-touch-icon" href="{{ shop_logo_url() }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ps-blue: #25b9d7;
            --ps-blue-dark: #1e94ab;
            --ps-top: #363a41;
            --ps-sidebar: #ebebeb;
            --ps-sidebar-text: #363a41;
            --ps-bg: #eff1f2;
            --ps-panel: #ffffff;
            --ps-muted: #6c868e;
            --ps-line: #dbe2e8;
            --ps-ink: #363a41;
            --danger: #f54c3e;
            --ok: #70b580;
            --warn: #fbbd3b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Open Sans", "Segoe UI", sans-serif;
            background: var(--ps-bg);
            color: var(--ps-ink);
            font-size: 14px;
        }
        a { color: inherit; text-decoration: none; }
        .ps-shell { min-height: 100vh; display: flex; flex-direction: column; }
        .ps-header {
            height: 50px;
            background: var(--ps-top);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            position: sticky;
            top: 0;
            z-index: 40;
        }
        .ps-header-left, .ps-header-right { display: flex; align-items: center; gap: 0.85rem; }
        .ps-logo {
            font-weight: 700;
            letter-spacing: 0.04em;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.55rem;
        }
        .ps-logo-img {
            width: 32px; height: 32px; border-radius: 50%; object-fit: cover;
            border: 2px solid rgba(255,255,255,.25); background: #fff; flex-shrink: 0;
        }
        .ps-logo-text { display: flex; align-items: baseline; gap: 0.35rem; }
        .ps-logo span { opacity: 0.55; font-weight: 500; font-size: 0.75rem; }
        .ps-search {
            background: #2b2e34;
            border: 1px solid #4a4f57;
            border-radius: 4px;
            color: #fff;
            padding: 0.4rem 0.7rem;
            min-width: 220px;
            font: inherit;
        }
        .ps-header a, .ps-header button.linkish {
            color: #c7ccd1;
            font-size: 0.85rem;
            background: none;
            border: 0;
            cursor: pointer;
            font: inherit;
        }
        .ps-header a:hover { color: #fff; }
        .ps-avatar {
            width: 28px; height: 28px; border-radius: 50%;
            background: var(--ps-blue); color: #fff;
            display: grid; place-items: center; font-size: 0.75rem; font-weight: 700;
        }
        .ps-body { display: grid; grid-template-columns: 210px 1fr; flex: 1; min-height: 0; }
        .ps-sidebar {
            background: var(--ps-sidebar);
            border-right: 1px solid #d3d8db;
            padding: 0.75rem 0 2rem;
            overflow-y: auto;
        }
        .ps-nav-section { margin-top: 0.85rem; }
        .ps-nav-title {
            padding: 0.45rem 1rem;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #6c7a80;
        }
        .ps-nav-item a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.55rem;
            padding: 0.55rem 1rem;
            color: var(--ps-sidebar-text);
            font-size: 0.9rem;
            border-left: 3px solid transparent;
        }
        .ps-nav-item a:hover { background: #e2e4e6; }
        .ps-nav-item a.active {
            background: #fff;
            border-left-color: var(--ps-blue);
            color: var(--ps-blue-dark);
            font-weight: 600;
        }
        .ps-nav-parent {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.55rem 1rem;
            color: var(--ps-sidebar-text);
            font-size: 0.9rem;
            font-weight: 600;
            border-left: 3px solid transparent;
            cursor: pointer;
            background: transparent;
            width: 100%;
            box-sizing: border-box;
        }
        .ps-nav-parent:hover { background: #e2e4e6; }
        .ps-nav-parent .nav-label { flex: 1; }
        .ps-nav-parent.open {
            background: #fff;
            border-left-color: var(--ps-blue);
            color: var(--ps-blue-dark);
        }
        .ps-nav-icon {
            width: 18px; height: 18px; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center;
            opacity: 0.85;
        }
        .ps-nav-icon svg { width: 18px; height: 18px; display: block; }
        .ps-nav-parent.open .ps-nav-icon,
        .ps-nav-item > a.active .ps-nav-icon { opacity: 1; color: var(--ps-blue-dark); }
        .ps-nav-children {
            background: #f3f3f3;
            padding-bottom: 0.35rem;
        }
        .ps-nav-children a {
            display: block;
            padding: 0.4rem 1rem 0.4rem 2.55rem;
            color: #5f6d73;
            font-size: 0.86rem;
            border-left: 3px solid transparent;
        }
        .ps-nav-children a:hover { background: #e8eaeb; color: var(--ps-ink); }
        .ps-nav-children a.active {
            color: var(--ps-blue-dark);
            font-weight: 600;
            background: #f7fbfc;
            border-left-color: transparent;
        }
        .chevron { font-size: 0.7rem; opacity: 0.7; margin-left: auto; }
        .ps-nav-parent.open .chevron { transform: rotate(180deg); display: inline-block; }
        .ps-main { min-width: 0; display: flex; flex-direction: column; }
        .ps-content { padding: 1.25rem 1.5rem 2rem; }
        .ps-breadcrumb { color: var(--ps-muted); font-size: 0.82rem; margin-bottom: 0.35rem; }
        .ps-breadcrumb a:hover { color: var(--ps-blue); }
        .page-title { font-size: 1.55rem; font-weight: 600; margin: 0 0 1rem; color: #25b9d7; }
        .page-sub { color: var(--ps-muted); margin: -0.5rem 0 1rem; }
        .grid { display: grid; gap: 1rem; }
        .grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .grid-2 { grid-template-columns: 1.4fr 1fr; }
        .grid-3 { grid-template-columns: 1fr 1.6fr 0.9fr; }
        .card {
            background: var(--ps-panel);
            border: 1px solid var(--ps-line);
            border-radius: 4px;
            padding: 1rem 1.1rem;
            box-shadow: 0 0 4px rgba(0,0,0,0.04);
        }
        .card h3 { margin: 0 0 0.85rem; font-size: 1rem; font-weight: 600; }
        .card-head {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 0.85rem;
        }
        .card-head h3 { margin: 0; }
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            background: #fff;
            border: 1px solid var(--ps-line);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        .kpi {
            padding: 0.9rem 0.85rem;
            border-right: 1px solid var(--ps-line);
            text-align: center;
        }
        .kpi:last-child { border-right: 0; }
        .kpi.active { background: var(--ps-blue); color: #fff; }
        .kpi .label { font-size: 0.78rem; opacity: 0.85; margin-bottom: 0.25rem; }
        .kpi .value { font-size: 1.15rem; font-weight: 700; }
        .kpi:not(.active) .label { color: var(--ps-muted); }
        .stat-line {
            display: flex; justify-content: space-between; align-items: center;
            padding: 0.55rem 0; border-bottom: 1px solid #f0f2f4;
            font-size: 0.9rem;
        }
        .stat-line:last-child { border-bottom: 0; }
        .stat-line strong { font-size: 1.05rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 0.65rem 0.4rem; border-bottom: 1px solid var(--ps-line); font-size: 0.88rem; }
        th { color: var(--ps-muted); font-weight: 600; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .btn {
            display: inline-flex; align-items: center; gap: 0.35rem;
            border: 0; border-radius: 4px; padding: 0.5rem 0.85rem; cursor: pointer; font-weight: 600; font-size: 0.85rem;
            font-family: inherit;
        }
        .btn-primary { background: var(--ps-blue); color: #fff; }
        .btn-primary:hover { background: var(--ps-blue-dark); }
        .btn-ghost { background: #fff; border: 1px solid var(--ps-line); color: var(--ps-ink); }
        .btn-danger { background: #fde8e6; color: var(--danger); }
        .btn-ok { background: #e8f5eb; color: #3d8b4f; }
        .flash { padding: 0.7rem 0.9rem; border-radius: 4px; margin-bottom: 1rem; }
        .flash-ok { background: #e8f5eb; color: #3d8b4f; }
        .flash-err { background: #fde8e6; color: var(--danger); }
        .form-row { display: grid; gap: 0.75rem; grid-template-columns: repeat(2, minmax(0,1fr)); margin-bottom: 0.85rem; }
        label { display: grid; gap: 0.35rem; font-size: 0.82rem; color: var(--ps-muted); }
        input, select, textarea {
            width: 100%; border: 1px solid #bbcdd2; border-radius: 3px; padding: 0.5rem 0.65rem; font: inherit; color: var(--ps-ink); background: #fff;
        }
        input:focus, select:focus, textarea:focus { outline: 0; border-color: var(--ps-blue); box-shadow: 0 0 0 2px rgba(37,185,215,0.15); }
        .badge { display: inline-block; padding: 0.12rem 0.45rem; border-radius: 3px; font-size: 0.72rem; font-weight: 600; }
        .badge-core { background: #e0f7fb; color: #1e94ab; }
        .badge-on { background: #e8f5eb; color: #3d8b4f; }
        .badge-off { background: #f0f2f4; color: var(--ps-muted); }
        .actions { display: flex; flex-wrap: wrap; gap: 0.4rem; }
        .chart-box { position: relative; height: 220px; }
        .chart-box.sm { height: 160px; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 0.35rem; }
        @media (max-width: 1100px) {
            .kpi-row { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .grid-3, .grid-2, .grid-4 { grid-template-columns: 1fr; }
        }
        @media (max-width: 860px) {
            .ps-body { grid-template-columns: 1fr; }
            .ps-sidebar { display: none; }
            .ps-search { display: none; }
            .kpi-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>
    @stack('styles')
</head>
<body>
@php
    $shopName = shop_name();
    $shopLogo = shop_logo_url();
    $userName = auth()->user()->name ?? 'Admin';
    $initials = collect(preg_split('/\s+/', trim($userName)))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    $authEmployee = auth()->user()?->employee;
@endphp
<div class="ps-shell">
    <header class="ps-header">
        <div class="ps-header-left">
            <div class="ps-logo">
                @if($shopLogo)
                    <img class="ps-logo-img" src="{{ $shopLogo }}" alt="{{ $shopName }}">
                @endif
                <span class="ps-logo-text">{{ strtoupper($shopName) }} <span>v{{ config('erp.version') }}</span></span>
            </div>
            <form action="{{ route('admin.customers.index') }}" method="get" style="margin:0;">
                <input class="ps-search" type="search" name="q" placeholder="{{ __('erp.common.search_placeholder') }}" aria-label="{{ __('erp.common.search') }}">
            </form>
        </div>
        <div class="ps-header-right">
            <a href="{{ route('admin.profile') }}" style="display:flex;align-items:center;gap:0.45rem;">
                @if($authEmployee?->avatar_url)
                    <img class="ps-avatar" src="{{ $authEmployee->avatar_url }}" alt="{{ $userName }}" style="object-fit:cover;padding:0;">
                @else
                    <span class="ps-avatar">{{ strtoupper($initials ?: 'A') }}</span>
                @endif
                {{ $userName }}
            </a>
            <form method="post" action="{{ route('admin.logout') }}" style="margin:0;">
                @csrf
                <button class="linkish" type="submit">{{ __('erp.common.sign_out') }}</button>
            </form>
        </div>
    </header>

    <div class="ps-body">
        <aside class="ps-sidebar">
            <nav>
                @foreach(auth()->user()->adminMenu() as $section)
                    <div class="ps-nav-section">
                        <div class="ps-nav-title">{{ $section['section'] }}</div>
                        @foreach($section['items'] as $item)
                            @php
                                $icon = $item['icon'] ?? null;
                                $matchPatterns = function ($match) {
                                    if (is_array($match)) {
                                        $patterns = $match;
                                    } else {
                                        $patterns = explode('|', (string) $match);
                                    }

                                    return collect($patterns)
                                        ->map(fn ($p) => trim((string) $p))
                                        ->filter()
                                        ->contains(fn ($p) => request()->routeIs($p));
                                };
                                $isOpen = ! empty($item['children']) && (
                                    collect($item['children'])->contains(fn ($c) => $matchPatterns($c['match'] ?? ''))
                                    || (! empty($item['match']) && $matchPatterns($item['match']))
                                );
                            @endphp
                            @if(!empty($item['children']))
                                @php
                                    $firstChild = $item['children'][0];
                                    $firstParams = $firstChild['route_params'] ?? [];
                                @endphp
                                <div class="ps-nav-item">
                                    <a href="{{ route($firstChild['route'], $firstParams) }}" class="ps-nav-parent {{ $isOpen ? 'open' : '' }}" style="text-decoration:none;">
                                        <span class="ps-nav-icon">@include('admin.partials.nav-icon', ['icon' => $icon])</span>
                                        <span class="nav-label">{{ $item['label'] }}</span>
                                        <span class="chevron">▾</span>
                                    </a>
                                    @if($isOpen)
                                        <div class="ps-nav-children">
                                            @foreach($item['children'] as $child)
                                                @php
                                                    $childParams = $child['route_params'] ?? [];
                                                    $childActive = $matchPatterns($child['match'] ?? '');
                                                    if ($childActive && isset($childParams['group'])) {
                                                        $currentGroup = request()->route('group') ?? 'shop';
                                                        $childActive = $currentGroup === $childParams['group'];
                                                    }
                                                @endphp
                                                <a href="{{ route($child['route'], $childParams) }}"
                                                   class="{{ $childActive ? 'active' : '' }}">
                                                    {{ $child['label'] }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="ps-nav-item">
                                    <a href="{{ route($item['route']) }}"
                                       class="{{ $matchPatterns($item['match'] ?? '') ? 'active' : '' }}">
                                        <span style="display:inline-flex;align-items:center;gap:0.55rem;">
                                            <span class="ps-nav-icon">@include('admin.partials.nav-icon', ['icon' => $icon])</span>
                                            {{ $item['label'] }}
                                        </span>
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </nav>
        </aside>

        <div class="ps-main">
            <main class="ps-content">
                @if(session('success'))
                    <div class="flash flash-ok">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="flash flash-err">{{ session('error') }}</div>
                @endif
                @if(isset($errors) && $errors->any())
                    <div class="flash flash-err">
                        <ul style="margin:0;padding-left:1.1rem;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</div>
@include('admin.partials.location-selects-script')
@include('admin.partials.auto-search-script')
@stack('scripts')
</body>
</html>
