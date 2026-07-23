@extends('admin.layouts.app')

@section('title', 'Webservice')

@section('content')
<style>
    .ws-empty {
        text-align:center; color:var(--ps-muted); padding:2rem 1rem;
        display:flex; flex-direction:column; align-items:center; gap:0.5rem;
    }
    .ws-empty svg { opacity:0.55; }
    .ws-form-row {
        display:grid; grid-template-columns: 260px 1fr; gap:1rem; align-items:start;
        padding:1rem 0; border-bottom:1px solid #f0f2f4;
    }
    .ws-form-row:last-of-type { border-bottom:0; }
    .ws-label { font-weight:600; color:var(--ps-ink); padding-top:0.35rem; text-align:right; }
    .ws-hint { color:var(--ps-muted); font-size:0.8rem; margin-top:0.45rem; line-height:1.45; max-width:40rem; }
    .ws-switch-row { display:flex; align-items:center; gap:0.75rem; }
    .ws-switch {
        position:relative; width:44px; height:24px; border-radius:12px; border:0; cursor:pointer;
        background:#bbcdd2; transition:background .15s; flex-shrink:0;
    }
    .ws-switch.on { background:#70b580; }
    .ws-switch::after {
        content:''; position:absolute; top:3px; left:3px; width:18px; height:18px;
        border-radius:50%; background:#fff; transition:left .15s;
    }
    .ws-switch.on::after { left:23px; }
    .ws-actions { display:flex; justify-content:flex-end; margin-top:1rem; padding-top:1rem; border-top:1px solid var(--ps-line); }
    .cu-toggle {
        display:inline-flex; align-items:center; justify-content:center;
        min-width:42px; height:22px; border-radius:11px; border:0; cursor:pointer;
        font-size:0.68rem; font-weight:700; color:#fff; padding:0 0.4rem; font-family:inherit;
    }
    .cu-toggle.on { background:#70b580; }
    .cu-toggle.off { background:#bbcdd2; }
    .cu-actions { display:flex; gap:0.3rem; align-items:center; justify-content:flex-end; }
    .cu-icon-btn {
        width:30px; height:30px; border:1px solid var(--ps-line); border-radius:3px;
        background:#fff; display:inline-grid; place-items:center; color:var(--ps-ink); cursor:pointer;
        text-decoration:none;
    }
    .cu-icon-btn:hover { border-color:var(--ps-blue); color:var(--ps-blue-dark); }
    .cu-menu { position:relative; display:inline-block; }
    .cu-menu-panel {
        display:none; position:absolute; right:0; top:110%; z-index:20;
        background:#fff; border:1px solid var(--ps-line); border-radius:4px;
        box-shadow:0 6px 18px rgba(0,0,0,.08); min-width:140px; padding:0.25rem 0;
    }
    .cu-menu.open .cu-menu-panel { display:block; }
    .cu-menu-panel a, .cu-menu-panel button {
        display:flex; align-items:center; gap:0.45rem; width:100%;
        padding:0.45rem 0.75rem; background:none; border:0; font:inherit; color:var(--ps-ink);
        text-align:left; cursor:pointer;
    }
    .cu-menu-panel a:hover, .cu-menu-panel button:hover { background:#f3f5f6; }
    .ws-key { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:0.82rem; }
    @media (max-width:800px) {
        .ws-form-row { grid-template-columns:1fr; }
        .ws-label { text-align:left; }
    }
</style>

<div class="ps-breadcrumb">Advanced Parameters &gt; Webservice</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">Webservice</h1>
    <div class="actions">
        <a href="{{ route('admin.webservice.create') }}" class="btn btn-primary">+ Add new webservice key</a>
    </div>
</div>

<div class="card" style="margin-bottom:1rem;">
    <div class="card-head">
        <h3 style="margin:0;">Webservice keys ({{ $keys->total() }})</h3>
    </div>

    @if($keys->total() === 0)
        <div class="ws-empty">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            No records found
        </div>
    @else
        <div style="overflow-x:auto;">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Key</th>
                    <th>Description</th>
                    <th>Enabled</th>
                    <th>Last used</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($keys as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td><span class="ws-key">{{ $item->key }}</span></td>
                        <td>{{ $item->description ?: '—' }}</td>
                        <td>
                            <form method="post" action="{{ route('admin.webservice.toggle', $item) }}">
                                @csrf @method('PUT')
                                <button type="submit" class="cu-toggle {{ $item->active ? 'on' : 'off' }}">{{ $item->active ? 'Yes' : 'No' }}</button>
                            </form>
                        </td>
                        <td>{{ $item->last_used_at?->format('Y-m-d H:i') ?: '—' }}</td>
                        <td>
                            <div class="cu-actions">
                                <a href="{{ route('admin.webservice.edit', $item) }}" class="cu-icon-btn" title="Edit">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                </a>
                                <div class="cu-menu">
                                    <button type="button" class="cu-icon-btn cu-menu-toggle" title="More">⋮</button>
                                    <div class="cu-menu-panel">
                                        <form method="post" action="{{ route('admin.webservice.destroy', $item) }}" onsubmit="return confirm('Delete this webservice key?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" style="color:var(--danger);">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:0.85rem;">{{ $keys->links() }}</div>
    @endif
</div>

<div class="card">
    <div class="card-head">
        <h3 style="margin:0;">Configuration</h3>
    </div>

    <form method="post" action="{{ route('admin.webservice.configuration') }}">
        @csrf
        @method('PUT')

        <div class="ws-form-row">
            <div class="ws-label">Enable ERP webservice</div>
            <div>
                <div class="ws-switch-row">
                    <input type="hidden" name="ERP_WEBSERVICE" value="0">
                    <button type="button" class="ws-switch {{ $enabled ? 'on' : '' }}" data-switch-for="ws-enabled" aria-label="Toggle webservice"></button>
                    <input type="checkbox" name="ERP_WEBSERVICE" value="1" id="ws-enabled" style="display:none;" @checked($enabled)>
                    <span data-switch-label-for="ws-enabled">{{ $enabled ? 'Yes' : 'No' }}</span>
                </div>
                <div class="ws-hint">
                    Allow external apps to access this ERP via API keys.<br>
                    Endpoint: <code>/api/webservice</code> — pass the key as <code>ws_key</code>, <code>Ws-Key</code> header, or Bearer token.<br>
                    Ensure your server supports GET, POST, PUT, PATCH, DELETE and HEAD.
                </div>
            </div>
        </div>

        <div class="ws-form-row">
            <div class="ws-label">Enable CGI mode for PHP</div>
            <div>
                <div class="ws-switch-row">
                    <input type="hidden" name="ERP_WEBSERVICE_CGI_MODE" value="0">
                    <button type="button" class="ws-switch {{ $cgiMode ? 'on' : '' }}" data-switch-for="ws-cgi" aria-label="Toggle CGI mode"></button>
                    <input type="checkbox" name="ERP_WEBSERVICE_CGI_MODE" value="1" id="ws-cgi" style="display:none;" @checked($cgiMode)>
                    <span data-switch-label-for="ws-cgi">{{ $cgiMode ? 'Yes' : 'No' }}</span>
                </div>
                <div class="ws-hint">
                    Only enable if PHP runs as CGI/FastCGI (not as an Apache module). Leave off for most XAMPP/LAMPP setups.
                </div>
            </div>
        </div>

        <div class="ws-actions">
            <button class="btn btn-primary" type="submit">Save</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.cu-menu-toggle').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        document.querySelectorAll('.cu-menu').forEach(m => { if (m !== btn.parentElement) m.classList.remove('open'); });
        btn.parentElement.classList.toggle('open');
    });
});
document.addEventListener('click', () => document.querySelectorAll('.cu-menu').forEach(m => m.classList.remove('open')));

document.querySelectorAll('[data-switch-for]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-switch-for');
        const input = document.getElementById(id);
        const label = document.querySelector('[data-switch-label-for="'+id+'"]');
        if (!input) return;
        input.checked = !input.checked;
        btn.classList.toggle('on', input.checked);
        if (label) label.textContent = input.checked ? 'Yes' : 'No';
    });
});
</script>
@endpush
