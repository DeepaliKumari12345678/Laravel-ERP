@extends('admin.layouts.app')

@section('title', 'Brands')

@section('content')
@php
    $filtersActive = collect(request()->only(['id', 'name', 'active']))->filter(fn ($v) => filled($v))->isNotEmpty();
    $addressFiltersActive = collect(request()->only(['address_id', 'address_brand', 'address_city', 'address_postcode', 'address_country']))
        ->filter(fn ($v) => filled($v))->isNotEmpty();
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
</style>

<div class="ps-breadcrumb">Brands &amp; Suppliers &gt; Brands</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">Brands</h1>
    <div class="actions">
        <a href="{{ route('admin.catalog.brands.create') }}" class="btn btn-primary">+ Add new brand</a>
        <a href="{{ route('admin.catalog.brands.addresses.create') }}" class="btn btn-primary">+ Add new brand address</a>
    </div>
</div>

<div class="bs-tabs">
    <a href="{{ route('admin.catalog.brands') }}" class="active">Brands</a>
    <a href="{{ route('admin.catalog.suppliers') }}">Suppliers</a>
</div>

<div class="card">
    <div class="card-head"><h3>Brands ({{ $brands->total() }})</h3></div>

    <form method="get" action="{{ route('admin.catalog.brands') }}" class="list-filters" data-auto-search="off">
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
                <a href="{{ route('admin.catalog.brands') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    <form method="post" action="{{ route('admin.catalog.brands.bulk') }}" id="brands-bulk-form" class="bulk-bar">
        @csrf
        <select name="action" id="brands-bulk-action" required>
            <option value="">Bulk actions</option>
            <option value="enable">Enable selection</option>
            <option value="disable">Disable selection</option>
            <option value="delete">Delete selected</option>
        </select>
        <button class="btn btn-primary" type="submit" id="brands-bulk-apply" disabled>Apply</button>
        <span class="team-muted" id="brands-bulk-count">0 item(s) selected</span>
    </form>

    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th style="width:2.25rem;"><input type="checkbox" id="brands-select-all" class="bulk-check" title="Select all"></th>
                <th>ID</th>
                <th>Logo</th>
                <th>Name</th>
                <th>Addresses</th>
                <th>Products</th>
                <th>Enabled</th>
                <th style="text-align:right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($brands as $brand)
                <tr>
                    <td>
                        <input type="checkbox" form="brands-bulk-form" name="ids[]" value="{{ $brand->id }}" class="brands-row-check bulk-check">
                    </td>
                    <td>{{ $brand->id }}</td>
                    <td>
                        @if($brand->logo_url)
                            <img src="{{ $brand->logo_url }}" alt="" class="logo-thumb">
                        @else
                            <div class="logo-placeholder">—</div>
                        @endif
                    </td>
                    <td><a class="name-link" href="{{ route('admin.catalog.brands.show', $brand) }}">{{ $brand->name }}</a></td>
                    <td>{{ $brand->addresses_count }}</td>
                    <td>{{ $brand->products_count }}</td>
                    <td>
                        <form method="post" action="{{ route('admin.catalog.brands.toggle', $brand) }}">
                            @csrf @method('PUT')
                            <button type="submit" class="cu-toggle {{ $brand->active ? 'on' : 'off' }}">{{ $brand->active ? 'Yes' : 'No' }}</button>
                        </form>
                    </td>
                    <td>
                        <div class="cu-actions">
                            <a href="{{ route('admin.catalog.brands.show', $brand) }}" class="cu-icon-btn" title="View">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </a>
                            <div class="cu-menu">
                                <button type="button" class="cu-icon-btn cu-menu-toggle" title="More">⋮</button>
                                <div class="cu-menu-panel">
                                    <a href="{{ route('admin.catalog.brands.edit', $brand) }}">Edit</a>
                                    <form method="post" action="{{ route('admin.catalog.brands.destroy', $brand) }}" onsubmit="return confirm('Delete this brand?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" style="color:var(--danger);">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" style="color:var(--ps-muted);">No brands found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:0.85rem;">{{ $brands->links() }}</div>
</div>

<div class="card" style="margin-top:1rem;">
    <div class="card-head"><h3>Addresses ({{ $addresses->total() }})</h3></div>

    <form method="get" action="{{ route('admin.catalog.brands') }}" class="list-filters" data-auto-search="off">
        <label>ID<input type="number" name="address_id" value="{{ request('address_id') }}"></label>
        <label>Brand name<input name="address_brand" value="{{ request('address_brand') }}"></label>
        <label>Zip/Postal code<input name="address_postcode" value="{{ request('address_postcode') }}"></label>
        <label>City<input name="address_city" value="{{ request('address_city') }}"></label>
        <label>Country<input name="address_country" value="{{ request('address_country') }}"></label>
        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Search</button>
            @if($addressFiltersActive)
                <a href="{{ route('admin.catalog.brands') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Brand</th>
                <th>Zip/Postal code</th>
                <th>City</th>
                <th>Country</th>
                <th style="text-align:right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($addresses as $address)
                <tr>
                    <td>{{ $address->id }}</td>
                    <td>{{ $address->brand?->name ?? '—' }}</td>
                    <td>{{ $address->postcode ?: '—' }}</td>
                    <td>{{ $address->city ?: '—' }}</td>
                    <td>{{ $address->country ?: '—' }}</td>
                    <td>
                        <div class="cu-actions">
                            <a href="{{ route('admin.catalog.brands.addresses.edit', $address) }}" class="cu-icon-btn" title="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </a>
                            <div class="cu-menu">
                                <button type="button" class="cu-icon-btn cu-menu-toggle" title="More">⋮</button>
                                <div class="cu-menu-panel">
                                    <form method="post" action="{{ route('admin.catalog.brands.addresses.destroy', $address) }}" onsubmit="return confirm('Delete this address?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" style="color:var(--danger);">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="color:var(--ps-muted);">No addresses found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:0.85rem;">{{ $addresses->links() }}</div>
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
    const form = document.getElementById('brands-bulk-form');
    if (!form) return;
    const selectAll = document.getElementById('brands-select-all');
    const rowChecks = () => Array.from(document.querySelectorAll('.brands-row-check'));
    const action = document.getElementById('brands-bulk-action');
    const applyBtn = document.getElementById('brands-bulk-apply');
    const countEl = document.getElementById('brands-bulk-count');

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
        if (e.target.classList.contains('brands-row-check') || e.target === action) sync();
    });
    form.addEventListener('submit', (e) => {
        const n = rowChecks().filter((el) => el.checked).length;
        if (n === 0 || !action.value) {
            e.preventDefault();
            return;
        }
        if (action.value === 'delete' && !confirm('Delete ' + n + ' selected brand(s)?')) e.preventDefault();
    });
    sync();
})();
</script>
@endpush
