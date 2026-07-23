<style>
    .team-head { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; margin-bottom:.9rem; }
    .team-head .page-title { margin:0 0 .25rem; }
    .team-tabs { display:flex; gap:1.25rem; border-bottom:1px solid var(--ps-line); margin-bottom:1rem; }
    .team-tabs a {
        color:var(--ps-muted); font-size:.86rem; font-weight:600; padding:.7rem .15rem;
        text-decoration:none; border-bottom:3px solid transparent; margin-bottom:-1px;
    }
    .team-tabs a:hover { color:var(--ps-ink); }
    .team-tabs a.active { color:var(--ps-ink); border-bottom-color:var(--ps-blue); }
    .team-toolbar {
        display:flex; justify-content:space-between; align-items:center; gap:1rem;
        padding:.85rem 1rem; border-bottom:1px solid var(--ps-line);
    }
    .team-toolbar h3 { margin:0; font-size:1rem; }
    .team-filters {
        display:grid; grid-template-columns:80px 1fr 1.2fr 170px 120px auto auto;
        gap:.4rem; align-items:end; padding:.8rem 1rem; background:#fbfcfc;
        border-bottom:1px solid var(--ps-line);
    }
    .team-avatar {
        width:38px; height:38px; border-radius:50%; object-fit:cover; display:grid; place-items:center;
        flex:0 0 38px; background:#e8f8fb; color:#168ca4; font-size:.72rem; font-weight:700;
        border:1px solid #ccebf1;
    }
    .team-person { display:flex; align-items:center; gap:.65rem; }
    .team-muted { color:var(--ps-muted); font-size:.76rem; }
    .team-actions { display:flex; gap:.35rem; justify-content:flex-end; }
    .team-icon-btn {
        width:32px; height:32px; display:grid; place-items:center; border:1px solid var(--ps-line);
        border-radius:4px; background:#fff; color:var(--ps-ink); text-decoration:none; cursor:pointer;
    }
    .team-icon-btn:hover { border-color:var(--ps-blue); color:var(--ps-blue); }
    .team-status {
        border:0; border-radius:12px; padding:.25rem .6rem; font-size:.72rem; font-weight:700; cursor:pointer;
    }
    .team-status.on { color:#247a3b; background:#eaf7ed; }
    .team-status.off { color:#8a4b53; background:#fbecef; }
    .team-form { max-width:720px; margin:0 auto; }
    .team-form-row {
        display:grid; grid-template-columns:200px 1fr; gap:1rem; align-items:start;
        padding:.9rem 0; border-bottom:1px solid #f0f2f4;
    }
    .team-form-label { text-align:right; padding-top:.55rem; font-size:.82rem; font-weight:600; }
    .team-form-actions {
        display:flex; justify-content:space-between; padding-top:1rem; margin-top:1rem;
    }
    .team-form-wrap { max-width:720px; margin:0 auto; }
    .team-avatar-preview {
        width:90px; height:90px; border-radius:50%; object-fit:cover; display:grid; place-items:center;
        background:#eef2f4; color:#72838a; font-size:1.25rem; font-weight:700; margin-bottom:.65rem;
        border:2px solid #dbe2e8;
    }
    .role-layout { display:grid; grid-template-columns:240px minmax(0,1fr); gap:1rem; }
    .role-list { padding:.5rem; }
    .role-list a {
        display:block; padding:.7rem .75rem; border-radius:4px; color:var(--ps-ink); text-decoration:none;
        font-size:.84rem; margin-bottom:.15rem;
    }
    .role-list a:hover { background:#f3f6f7; }
    .role-list a.active { background:#263238; color:#fff; }
    .permission-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; }
    .permission-item {
        display:flex; align-items:center; gap:.65rem; border:1px solid var(--ps-line);
        border-radius:5px; padding:.75rem; color:var(--ps-ink); background:#fff;
    }
    .permission-item input { width:auto; }
    @media (max-width:900px) {
        .team-filters { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .role-layout { grid-template-columns:1fr; }
    }
    @media (max-width:620px) {
        .team-head { flex-direction:column; }
        .team-form-row { grid-template-columns:1fr; }
        .team-form-label { text-align:left; }
        .permission-grid, .team-filters { grid-template-columns:1fr; }
    }
</style>

<div class="team-tabs">
    @if(auth()->user()?->hasPermission('tab.employees'))
        <a href="{{ route('admin.employees.index') }}" class="{{ request()->routeIs('admin.employees.*') ? 'active' : '' }}">Employees</a>
    @endif
    @if(auth()->user()?->hasPermission('tab.roles'))
        <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.index', 'admin.roles.create', 'admin.roles.edit') ? 'active' : '' }}">Roles</a>
        <a href="{{ route('admin.roles.permissions') }}" class="{{ request()->routeIs('admin.roles.permissions*') ? 'active' : '' }}">Permissions</a>
    @endif
</div>
