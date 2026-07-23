@extends('admin.layouts.app')

@section('title', 'Suppliers')

@section('content')
@php
    $filtersActive = collect(request()->only(['id', 'name', 'active']))->filter(fn ($v) => filled($v))->isNotEmpty();
@endphp

<style>
    .bs-tabs { display:flex; gap:0; border-bottom:1px solid var(--ps-line); margin-bottom:1rem; }
    .bs-tabs a {
        padding:0.7rem 1.1rem; text-decoration:none; color:var(--ps-muted); font-weight:600;
        border-bottom:2px solid transparent; margin-bottom:-1px;
    }
    .bs-tabs a.active { color:var(--ps-ink); border-bottom-color:#25b9d7; }
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
    .list-filters { display:flex; flex-wrap:wrap; gap:0.4rem; align-items:end; margin-bottom:0.85rem; }
    .list-filters label { flex:1 1 110px; min-width:90px; }
    .list-filters .filter-actions { display:flex; gap:0.4rem; flex:0 0 auto; }
    .bulk-bar { display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap; margin-bottom:0.75rem; }
    .bulk-bar select { width:auto; min-width:11rem; }
    .bulk-check {
        width:auto !important; min-width:1rem; height:1rem; margin:0; padding:0;
        accent-color:#25b9d7; cursor:pointer;
    }
    .logo-thumb {
        width:42px; height:42px; object-fit:contain; border:1px solid var(--ps-line);
        border-radius:4px; background:#f4f6f7; display:block;
    }
    .logo-placeholder {
        width:42px; height:42px; border:1px solid var(--ps-line); border-radius:4px;
        background:#f4f6f7; color:var(--ps-muted); display:grid; place-items:center;
        font-size:.65rem; font-weight:700;
    }
    .name-link { color: var(--ps-blue-dark); text-decoration: none; font-weight: 600; }
    .name-link:hover { text-decoration: underline; }
    .info-banner {
        background:#e8f7fb; border:1px solid #b9e4ef; border-radius:4px;
        padding:0.85rem 1rem; font-size:0.88rem; color:#1e6475; margin-bottom:1rem;
    }
</style>

<div class="ps-breadcrumb">Brands &amp; Suppliers &gt; Suppliers</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">Suppliers</h1>
    <div class="actions">
        <a href="{{ route('admin.catalog.suppliers.create') }}" class="btn btn-primary">+ Add new supplier</a>
    </div>
</div>

<div class="bs-tabs">
    <a href="{{ route('admin.catalog.brands') }}">Brands</a>
    <a href="{{ route('admin.catalog.suppliers') }}" class="active">Suppliers</a>
</div>

@unless($suppliersDisplayEnabled)
    <div class="info-banner">
        The display of your suppliers is disabled on your store. Go to Shop Parameters &gt; General to edit settings.
    </div>
@endunless

<div class="card">
    <div class="card-head"><h3>Suppliers ({{ $suppliers->total() }})</h3></div>

    <form method="get" action="{{ route('admin.catalog.suppliers') }}" class="list-filters" data-auto-search="off">
        <label>ID<input type="number" name="id" value="{{ request('id') }}"></label>
        <label>Name<input name="name" value="{{ request('name') }}"></label>
        <label>Enabled
            <select name="active">
                <option value="">All</option>
                <option value="1" @selected(request('active') === '1')>Yes</option>
                <option value="0" @selected(request('active') === '0')>No</option>
            </select>
        </label>
        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Search</button>
            @if($filtersActive)
                <a href="{{ route('admin.catalog.suppliers') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    <form method="post" action="{{ route('admin.catalog.suppliers.bulk') }}" id="suppliers-bulk-form" class="bulk-bar">
        @csrf
        <select name="action" id="suppliers-bulk-action" required>
            <option value="">Bulk actions</option>
            <option value="enable">Enable selection</option>
            <option value="disable">Disable selection</option>
            <option value="delete">Delete selected</option>
        </select>
        <button class="btn btn-primary" type="submit" id="suppliers-bulk-apply" disabled>Apply</button>
        <span class="team-muted" id="suppliers-bulk-count">0 item(s) selected</span>
    </form>

    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th style="width:2.25rem;"><input type="checkbox" id="suppliers-select-all" class="bulk-check" title="Select all"></th>
                <th>ID</th>
                <th>Logo</th>
                <th>Name</th>
                <th>Number of products</th>
                <th>Enabled</th>
                <th style="text-align:right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($suppliers as $supplier)
                <tr>
                    <td>
                        <input type="checkbox" form="suppliers-bulk-form" name="ids[]" value="{{ $supplier->id }}" class="suppliers-row-check bulk-check">
                    </td>
                    <td>{{ $supplier->id }}</td>
                    <td>
                        @if($supplier->logo_url)
                            <img src="{{ $supplier->logo_url }}" alt="" class="logo-thumb">
                        @else
                            <div class="logo-placeholder">—</div>
                        @endif
                    </td>
                    <td><a class="name-link" href="{{ route('admin.catalog.suppliers.show', $supplier) }}">{{ $supplier->name }}</a></td>
                    <td>{{ $supplier->products_count }}</td>
                    <td>
                        <form method="post" action="{{ route('admin.catalog.suppliers.toggle', $supplier) }}">
                            @csrf @method('PUT')
                            <button type="submit" class="cu-toggle {{ $supplier->active ? 'on' : 'off' }}">{{ $supplier->active ? 'Yes' : 'No' }}</button>
                        </form>
                    </td>
                    <td>
                        <div class="cu-actions">
                            <a href="{{ route('admin.catalog.suppliers.show', $supplier) }}" class="cu-icon-btn" title="View">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </a>
                            <div class="cu-menu">
                                <button type="button" class="cu-icon-btn cu-menu-toggle" title="More">⋮</button>
                                <div class="cu-menu-panel">
                                    <a href="{{ route('admin.catalog.suppliers.edit', $supplier) }}">Edit</a>
                                    <form method="post" action="{{ route('admin.catalog.suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete this supplier?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" style="color:var(--danger);">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="color:var(--ps-muted);">No suppliers found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:0.85rem;">{{ $suppliers->links() }}</div>
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

(function () {
    const form = document.getElementById('suppliers-bulk-form');
    if (!form) return;
    const selectAll = document.getElementById('suppliers-select-all');
    const rowChecks = () => Array.from(document.querySelectorAll('.suppliers-row-check'));
    const action = document.getElementById('suppliers-bulk-action');
    const applyBtn = document.getElementById('suppliers-bulk-apply');
    const countEl = document.getElementById('suppliers-bulk-count');

    function sync() {
        const rows = rowChecks();
        const selected = rows.filter((el) => el.checked);
        const n = selected.length;
        if (selectAll) {
            selectAll.checked = rows.length > 0 && n === rows.length;
            selectAll.indeterminate = n > 0 && n < rows.length;
        }
        if (countEl) countEl.textContent = n + ' item(s) selected';
        if (applyBtn) applyBtn.disabled = n === 0 || !action.value;
    }

    selectAll?.addEventListener('change', () => {
        rowChecks().forEach((el) => { el.checked = selectAll.checked; });
        sync();
    });
    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('suppliers-row-check') || e.target === action) sync();
    });
    form.addEventListener('submit', (e) => {
        const n = rowChecks().filter((el) => el.checked).length;
        if (n === 0 || !action.value) {
            e.preventDefault();
            return;
        }
        if (action.value === 'delete' && !confirm('Delete ' + n + ' selected supplier(s)?')) e.preventDefault();
    });
    sync();
})();
</script>
@endpush
