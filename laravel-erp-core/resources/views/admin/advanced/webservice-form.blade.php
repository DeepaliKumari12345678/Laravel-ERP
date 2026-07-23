@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Edit webservice key' : 'Add new webservice key')

@section('content')
@php
    $permissions = old('permissions', $webserviceKey->permissions ?? []);
@endphp
<style>
    .ws-wrap { max-width: 980px; margin: 0 auto; }
    .ws-row {
        display:grid; grid-template-columns: 200px minmax(0,1fr); gap:1.1rem; align-items:start;
        padding:1rem 0; border-bottom:1px solid #f0f2f4;
    }
    .ws-row:last-of-type { border-bottom:0; }
    .ws-label { font-weight:600; color:var(--ps-ink); padding-top:0.5rem; text-align:right; }
    .ws-label .req { color:var(--danger); }
    .ws-hint { color:var(--ps-muted); font-size:0.78rem; margin-top:0.35rem; }
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
    .ws-key-row { display:flex; gap:0.5rem; align-items:center; }
    .ws-key-row input { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
    .ws-perm-table { width:100%; border-collapse:collapse; }
    .ws-perm-table th, .ws-perm-table td {
        border:1px solid var(--ps-line); padding:0.45rem 0.55rem; text-align:center; font-size:0.86rem;
    }
    .ws-perm-table th:first-child, .ws-perm-table td:first-child { text-align:left; font-weight:600; }
    .ws-perm-table input { width:auto; }
    .ws-actions {
        display:flex; justify-content:space-between; gap:1rem; margin-top:1.25rem;
        max-width:980px; margin-left:auto; margin-right:auto;
    }
    @media (max-width:720px) {
        .ws-row { grid-template-columns:1fr; }
        .ws-label { text-align:left; }
    }
</style>

<div class="ps-breadcrumb">
    Advanced Parameters &gt;
    <a href="{{ route('admin.webservice.index') }}">Webservice</a> &gt;
    {{ $mode === 'edit' ? 'Edit' : 'Add' }}
</div>

<div style="margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">
        {{ $mode === 'edit' ? 'Edit webservice key' : 'Add new webservice key' }}
    </h1>
</div>

<form method="post"
      action="{{ $mode === 'edit' ? route('admin.webservice.update', $webserviceKey) : route('admin.webservice.store') }}">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif

    <div class="card ws-wrap">
        <div class="card-head"><h3 style="margin:0;">Key</h3></div>

        <div class="ws-row">
            <div class="ws-label">Key <span class="req">*</span></div>
            <div>
                <div class="ws-key-row">
                    <input id="ws-key" name="key" value="{{ old('key', $webserviceKey->key) }}" required maxlength="32" minlength="32">
                    <button type="button" class="btn btn-ghost" id="ws-generate">Generate</button>
                </div>
                <div class="ws-hint">32 characters (a-z A-Z 0-9).</div>
            </div>
        </div>

        <div class="ws-row">
            <div class="ws-label">Key description</div>
            <div>
                <input name="description" value="{{ old('description', $webserviceKey->description) }}" maxlength="255">
                <div class="ws-hint">Quick description of the key: who it is for, what permissions it has…</div>
            </div>
        </div>

        <div class="ws-row">
            <div class="ws-label">Status</div>
            <div class="ws-switch-row">
                <input type="hidden" name="active" value="0">
                <button type="button" class="ws-switch {{ old('active', $webserviceKey->active) ? 'on' : '' }}" id="ws-active-switch"></button>
                <input type="checkbox" name="active" value="1" id="ws-active" style="display:none;" @checked(old('active', $webserviceKey->active))>
                <span id="ws-active-label">{{ old('active', $webserviceKey->active) ? 'Yes' : 'No' }}</span>
            </div>
        </div>

        <div class="ws-row">
            <div class="ws-label">Permissions</div>
            <div style="overflow-x:auto;">
                <table class="ws-perm-table">
                    <thead>
                    <tr>
                        <th>Resource</th>
                        @foreach($methods as $method)
                            <th>{{ $method }}</th>
                        @endforeach
                        <th>All</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($resources as $resource)
                        <tr>
                            <td>{{ $resource }}</td>
                            @foreach($methods as $method)
                                @php($checked = in_array($method, (array) ($permissions[$resource] ?? []), true))
                                <td>
                                    <input type="checkbox"
                                           class="ws-perm"
                                           data-resource="{{ $resource }}"
                                           name="permissions[{{ $resource }}][]"
                                           value="{{ $method }}"
                                           @checked($checked)>
                                </td>
                            @endforeach
                            <td>
                                <input type="checkbox" class="ws-perm-all" data-resource="{{ $resource }}" title="Select all methods">
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="ws-hint" style="margin-top:0.65rem;">
                    API base URL: <code>{{ url('/api') }}</code> — pass the key as <code>?ws_key=YOUR_KEY</code> or header <code>Ws-Key</code>.
                </div>
            </div>
        </div>
    </div>

    <div class="ws-actions">
        <a href="{{ route('admin.webservice.index') }}" class="btn btn-ghost">Cancel</a>
        <button class="btn btn-primary" type="submit">Save</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const sw = document.getElementById('ws-active-switch');
    const input = document.getElementById('ws-active');
    const label = document.getElementById('ws-active-label');
    sw?.addEventListener('click', () => {
        input.checked = !input.checked;
        sw.classList.toggle('on', input.checked);
        if (label) label.textContent = input.checked ? 'Yes' : 'No';
    });

    document.getElementById('ws-generate')?.addEventListener('click', () => {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let out = '';
        for (let i = 0; i < 32; i++) out += chars[Math.floor(Math.random() * chars.length)];
        const el = document.getElementById('ws-key');
        if (el) el.value = out;
    });

    document.querySelectorAll('.ws-perm-all').forEach((all) => {
        const resource = all.getAttribute('data-resource');
        const boxes = () => Array.from(document.querySelectorAll('.ws-perm[data-resource="'+resource+'"]'));
        const sync = () => {
            const list = boxes();
            all.checked = list.length > 0 && list.every((b) => b.checked);
            all.indeterminate = list.some((b) => b.checked) && !all.checked;
        };
        all.addEventListener('change', () => {
            boxes().forEach((b) => { b.checked = all.checked; });
        });
        boxes().forEach((b) => b.addEventListener('change', sync));
        sync();
    });
})();
</script>
@endpush
