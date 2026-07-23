@extends('admin.layouts.app')

@section('title', 'Products')

@section('content')
@php
    $filtersActive = collect(request()->only([
        'id', 'name', 'sku', 'category_id', 'brand_id', 'supplier_id', 'type', 'active',
    ]))->filter(fn ($v) => filled($v))->isNotEmpty();
@endphp

<style>
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
        box-shadow:0 6px 18px rgba(0,0,0,.08); min-width:130px; padding:0.25rem 0;
    }
    .cu-menu.open .cu-menu-panel { display:block; }
    .cu-menu-panel a, .cu-menu-panel button {
        display:flex; align-items:center; gap:0.45rem; width:100%;
        padding:0.45rem 0.75rem; background:none; border:0; font:inherit; color:var(--ps-ink);
        text-align:left; cursor:pointer;
    }
    .cu-menu-panel a:hover, .cu-menu-panel button:hover { background:#f3f5f6; }
    .product-list-image {
        width:42px; height:42px; object-fit:cover; border:1px solid var(--ps-line);
        border-radius:4px; background:#f4f6f7; display:block;
    }
    .product-list-placeholder {
        width:42px; height:42px; border:1px solid var(--ps-line); border-radius:4px;
        background:#f4f6f7; color:var(--ps-muted); display:grid; place-items:center;
        font-size:.65rem; font-weight:700;
    }
    .product-filters {
        display:flex; flex-wrap:wrap; gap:0.4rem; align-items:end; margin-bottom:0.85rem;
    }
    .product-filters label { flex:1 1 110px; min-width:90px; }
    .product-filters .filter-actions {
        display:flex; gap:0.4rem; align-items:center; flex:0 0 auto;
    }
    .bulk-bar {
        display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap; margin-bottom:0.75rem;
    }
    .bulk-bar select { width:auto; min-width:11rem; }
    .bulk-check {
        width:auto !important; min-width:1rem; height:1rem; margin:0; padding:0;
        accent-color:#25b9d7; cursor:pointer;
    }
</style>

<div class="ps-breadcrumb">Sell &gt; Catalog &gt; Products</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">Products</h1>
    <div class="actions">
        <a href="{{ route('admin.catalog.products.create') }}" class="btn btn-primary">+ Add new product</a>
    </div>
</div>

<div class="kpi-row" style="grid-template-columns:repeat(5,minmax(0,1fr));">
    <div class="kpi">
        <div class="label">Total products</div>
        <div class="value">{{ $kpis['total'] }}</div>
    </div>
    <div class="kpi">
        <div class="label">Active</div>
        <div class="value">{{ $kpis['active'] }}</div>
    </div>
    <div class="kpi">
        <div class="label">Low stock</div>
        <div class="value">{{ $kpis['low_stock'] }}</div>
    </div>
    <div class="kpi">
        <div class="label">Packs</div>
        <div class="value">{{ $kpis['packs'] }}</div>
    </div>
    <div class="kpi active">
        <div class="label">Virtual</div>
        <div class="value">{{ $kpis['virtual'] }}</div>
    </div>
</div>

<div class="card" style="margin-top:1rem;">
    <div class="card-head">
        <h3>Products ({{ $products->total() }})</h3>
    </div>

    <form method="get" action="{{ route('admin.catalog.products') }}" class="product-filters" data-auto-search="off">
        <label>ID<input type="number" name="id" value="{{ request('id') }}"></label>
        <label>Name<input name="name" value="{{ request('name') }}"></label>
        <label>Reference<input name="sku" value="{{ request('sku') }}"></label>
        <label>Category
            <select name="category_id">
                <option value="">—</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Brand
            <select name="brand_id">
                <option value="">—</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" @selected((string) request('brand_id') === (string) $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Supplier
            <select name="supplier_id">
                <option value="">—</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Type
            <select name="type">
                <option value="">—</option>
                @foreach($typeOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>Status
            <select name="active">
                <option value="">All</option>
                <option value="1" @selected(request('active') === '1')>Yes</option>
                <option value="0" @selected(request('active') === '0')>No</option>
            </select>
        </label>
        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Search</button>
            @if($filtersActive)
                <a href="{{ route('admin.catalog.products') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    <form method="post" action="{{ route('admin.catalog.products.bulk') }}" id="products-bulk-form" class="bulk-bar">
        @csrf
        <select name="action" id="bulk-action" required>
            <option value="">Bulk actions</option>
            <option value="enable">Enable selection</option>
            <option value="disable">Disable selection</option>
            <option value="delete">Delete selected</option>
        </select>
        <button class="btn btn-primary" type="submit" id="bulk-apply" disabled>Apply</button>
        <span class="team-muted" id="bulk-count">0 item(s) selected</span>
    </form>

    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th style="width:2.25rem;">
                    <input type="checkbox" id="bulk-select-all" class="bulk-check" title="Select all" aria-label="Select all">
                </th>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Reference</th>
                <th>Category</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Status</th>
                <th style="text-align:right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($products as $product)
                <tr>
                    <td>
                        <input type="checkbox" form="products-bulk-form" name="ids[]" value="{{ $product->id }}" class="bulk-row-check bulk-check" aria-label="Select product {{ $product->id }}">
                    </td>
                    <td>{{ $product->id }}</td>
                    <td>
                        @if($product->image_url)
                            <img class="product-list-image" src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                        @else
                            <div class="product-list-placeholder">—</div>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $product->name }}</strong>
                        @if(($typeOptions[$product->type] ?? null) && $product->type !== 'standard')
                            <div style="color:var(--ps-muted);font-size:.76rem;">{{ $typeOptions[$product->type] }}</div>
                        @endif
                    </td>
                    <td>{{ $product->sku ?: '—' }}</td>
                    <td>{{ $product->category?->name ?? '—' }}</td>
                    <td>{{ number_format((float) $product->price, 2) }} {{ $currency }}</td>
                    <td>{{ $product->track_inventory ? number_format((float) $product->quantity, 0) : '—' }}</td>
                    <td>
                        <form method="post" action="{{ route('admin.catalog.products.toggle', $product) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="field" value="active">
                            <button type="submit" class="cu-toggle {{ $product->active ? 'on' : 'off' }}">{{ $product->active ? 'Yes' : 'No' }}</button>
                        </form>
                    </td>
                    <td>
                        <div class="cu-actions">
                            <a href="{{ route('admin.catalog.products.edit', $product) }}" class="cu-icon-btn" title="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </a>
                            <div class="cu-menu">
                                <button type="button" class="cu-icon-btn cu-menu-toggle" title="More actions">⋮</button>
                                <div class="cu-menu-panel">
                                    <a href="{{ route('admin.catalog.products.preview', $product) }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Preview
                                    </a>
                                    <form method="post" action="{{ route('admin.catalog.products.duplicate', $product) }}">
                                        @csrf
                                        <button type="submit">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                            Duplicate
                                        </button>
                                    </form>
                                    <form method="post" action="{{ route('admin.catalog.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="color:var(--danger);">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" style="color:var(--ps-muted);">No products found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:0.85rem;">{{ $products->links() }}</div>
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
    const form = document.getElementById('products-bulk-form');
    if (!form) return;

    const selectAll = document.getElementById('bulk-select-all');
    const rowChecks = () => Array.from(document.querySelectorAll('.bulk-row-check'));
    const action = document.getElementById('bulk-action');
    const applyBtn = document.getElementById('bulk-apply');
    const countEl = document.getElementById('bulk-count');

    function syncBulkUi() {
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
        syncBulkUi();
    });

    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('bulk-row-check') || e.target === action) {
            syncBulkUi();
        }
    });

    form.addEventListener('submit', (e) => {
        const n = rowChecks().filter((el) => el.checked).length;
        if (n === 0) {
            e.preventDefault();
            alert('Please select at least one product.');
            return;
        }
        if (!action.value) {
            e.preventDefault();
            alert('Please choose a bulk action.');
            return;
        }
        if (action.value === 'delete' && !confirm('Delete ' + n + ' selected product(s)?')) {
            e.preventDefault();
        }
    });

    syncBulkUi();
})();
</script>
@endpush
