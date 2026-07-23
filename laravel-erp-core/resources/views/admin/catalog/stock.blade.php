@extends('admin.layouts.app')

@section('title', 'Stock management')

@section('content')
@php
    $q = request('q');
    $filtersOpen = collect(request()->only(['supplier_id', 'active']))->filter(fn ($v) => filled($v))->isNotEmpty();
@endphp

<style>
    .st-tabs { display:flex; gap:0; border-bottom:1px solid var(--ps-line); margin-bottom:1rem; }
    .st-tabs a {
        padding:0.75rem 1.15rem; text-decoration:none; color:var(--ps-muted); font-weight:600;
        border:1px solid transparent; border-bottom:0; margin-bottom:-1px; border-radius:4px 4px 0 0;
    }
    .st-tabs a.active {
        color:var(--ps-ink); background:#fff; border-color:var(--ps-line); border-bottom-color:#fff;
    }
    .st-search {
        display:flex; gap:0.5rem; align-items:end; margin-bottom:0.75rem; flex-wrap:wrap;
    }
    .st-search label { flex:1 1 280px; margin:0; }
    .st-search .filter-actions { display:flex; gap:0.4rem; flex:0 0 auto; }
    .st-advanced {
        border:1px solid var(--ps-line); border-radius:4px; margin-bottom:0.85rem; background:#fff;
    }
    .st-advanced summary {
        list-style:none; cursor:pointer; padding:0.7rem 0.9rem; font-weight:600; color:var(--ps-ink);
        display:flex; justify-content:space-between; align-items:center;
    }
    .st-advanced summary::-webkit-details-marker { display:none; }
    .st-advanced-body {
        display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:0.6rem;
        padding:0 0.9rem 0.9rem; border-top:1px solid #f0f2f4;
    }
    .st-options {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        margin-bottom:0.85rem;
    }
    .st-options label { display:flex; align-items:center; gap:0.45rem; margin:0; color:var(--ps-ink); font-size:0.9rem; }
    .st-options input { width:auto; }
    .st-bulk {
        display:flex; align-items:center; gap:0.55rem; flex-wrap:wrap; margin-bottom:0.85rem;
        padding:0.65rem 0.75rem; border:1px solid var(--ps-line); border-radius:4px; background:#fafbfc;
    }
    .st-bulk input[type="number"] { width:110px; }
    .st-bulk .bulk-check { width:auto !important; min-width:1rem; height:1rem; margin:0; accent-color:#25b9d7; }
    .st-thumb {
        width:40px; height:40px; object-fit:cover; border:1px solid var(--ps-line);
        border-radius:3px; background:#f4f6f7; display:block;
    }
    .st-thumb-ph {
        width:40px; height:40px; border:1px solid var(--ps-line); border-radius:3px;
        background:#f4f6f7; display:grid; place-items:center; color:var(--ps-muted); font-size:0.65rem;
    }
    .st-name-cell { display:flex; align-items:center; gap:0.65rem; }
    .st-status-ok { color:#70b580; display:inline-grid; place-items:center; }
    .st-status-off { color:#bbcdd2; display:inline-grid; place-items:center; }
    .st-qty-form { display:flex; gap:0.3rem; align-items:center; }
    .st-qty-form input { width:88px; }
    .st-qty-form button {
        width:30px; height:30px; border:1px solid var(--ps-line); border-radius:3px;
        background:#fff; cursor:pointer; display:inline-grid; place-items:center;
    }
    .st-qty-form button:hover { border-color:var(--ps-blue); color:var(--ps-blue-dark); }
    .st-delta {
        display:inline-flex; align-items:center; justify-content:center;
        min-width:3rem; padding:0.2rem 0.55rem; border-radius:12px; font-weight:700; font-size:0.82rem;
    }
    .st-delta.plus { background:#e7f5ec; color:#2f7a45; }
    .st-delta.minus { background:#fde8e6; color:#b42318; }
    .st-delta.zero { background:#eef1f3; color:var(--ps-muted); }
    .bulk-check {
        width:auto !important; min-width:1rem; height:1rem; margin:0; padding:0;
        accent-color:#25b9d7; cursor:pointer;
    }
</style>

<div class="ps-breadcrumb">
    Catalog &gt; Stock management &gt; {{ $tab === 'movements' ? 'Movements' : 'Stock' }}
</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">Stock management</h1>
</div>

<div class="st-tabs">
    <a href="{{ route('admin.catalog.stock') }}" class="{{ $tab === 'stock' ? 'active' : '' }}">Stock</a>
    <a href="{{ route('admin.catalog.stock', ['tab' => 'movements']) }}" class="{{ $tab === 'movements' ? 'active' : '' }}">Movements</a>
</div>

@if($tab === 'stock')
    <div class="card">
        <form method="get" action="{{ route('admin.catalog.stock') }}" class="st-search" data-auto-search="off">
            <label>
                Search products (search by name, reference, supplier)
                <input name="q" value="{{ $q }}" placeholder="Search products (search by name, reference, supplier)">
            </label>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Search</button>
                @if(filled($q) || $filtersOpen || $lowFirst)
                    <a href="{{ route('admin.catalog.stock') }}" class="btn btn-ghost">Reset</a>
                @endif
            </div>
        </form>

        <details class="st-advanced" @if($filtersOpen) open @endif>
            <summary>
                <span>Advanced filters</span>
                <span aria-hidden="true">▾</span>
            </summary>
            <form method="get" action="{{ route('admin.catalog.stock') }}" class="st-advanced-body">
                @if(filled($q)) <input type="hidden" name="q" value="{{ $q }}"> @endif
                <label>Supplier
                    <select name="supplier_id">
                        <option value="">All</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Status
                    <select name="active">
                        <option value="">All</option>
                        <option value="1" @selected(request('active') === '1')>Enabled</option>
                        <option value="0" @selected(request('active') === '0')>Disabled</option>
                    </select>
                </label>
                <div style="display:flex;align-items:end;">
                    <button class="btn btn-primary" type="submit">Apply filters</button>
                </div>
            </form>
        </details>

        <form method="get" action="{{ route('admin.catalog.stock') }}" class="st-options">
            @foreach(request()->except(['low_first', 'page']) as $key => $value)
                @if(is_array($value))
                    @continue
                @endif
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <label>
                <input type="checkbox" name="low_first" value="1" onchange="this.form.submit()" @checked($lowFirst)>
                Display products below low stock level first
            </label>
        </form>

        <form method="post" action="{{ route('admin.catalog.stock.bulk') }}" id="stock-bulk-form" class="st-bulk">
            @csrf
            <input type="checkbox" id="stock-select-all" class="bulk-check" title="Select all">
            <strong>Bulk edit quantity</strong>
            <input type="number" step="1" name="quantity" id="bulk-qty" placeholder="± qty" required>
            <button class="btn btn-primary" type="submit" id="bulk-apply" disabled>Apply new quantity</button>
            <span class="team-muted" id="bulk-count">0 selected</span>
        </form>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                <tr>
                    <th style="width:2rem;"></th>
                    <th>ID</th>
                    <th></th>
                    <th>Product name</th>
                    <th>Reference</th>
                    <th>Supplier</th>
                    <th>Status</th>
                    <th>Physical</th>
                    <th>Reserved</th>
                    <th>Available</th>
                    <th>Edit quantity</th>
                </tr>
                </thead>
                <tbody>
                @forelse($products as $product)
                    @php
                        $reserved = (float) ($product->reserved_quantity ?? 0);
                        $available = (float) $product->quantity;
                        $physical = $available + $reserved;
                    @endphp
                    <tr>
                        <td>
                            <input type="checkbox" form="stock-bulk-form" name="ids[]" value="{{ $product->id }}" class="stock-row-check bulk-check">
                        </td>
                        <td>{{ $product->id }}</td>
                        <td>
                            @if($product->image_url)
                                <img src="{{ $product->image_url }}" alt="" class="st-thumb">
                            @else
                                <div class="st-thumb-ph">—</div>
                            @endif
                        </td>
                        <td>
                            <div class="st-name-cell">
                                <strong>{{ $product->name }}</strong>
                            </div>
                        </td>
                        <td>{{ $product->sku ?: '—' }}</td>
                        <td>{{ $product->supplier?->name ?? 'N/A' }}</td>
                        <td>
                            @if($product->active)
                                <span class="st-status-ok" title="Enabled">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                            @else
                                <span class="st-status-off" title="Disabled">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                </span>
                            @endif
                        </td>
                        <td>{{ number_format($physical, 0) }}</td>
                        <td>{{ number_format($reserved, 0) }}</td>
                        <td><strong>{{ number_format($available, 0) }}</strong></td>
                        <td>
                            <form method="post" action="{{ route('admin.catalog.stock.adjust') }}" class="st-qty-form">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input type="hidden" name="type" value="delta">
                                <input type="number" step="1" name="quantity" placeholder="±" required>
                                <button type="submit" title="Apply">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" style="color:var(--ps-muted);">No products found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:0.85rem;">{{ $products->links() }}</div>
    </div>
@else
    <div class="card">
        <form method="get" action="{{ route('admin.catalog.stock') }}" class="st-search" data-auto-search="off">
            <input type="hidden" name="tab" value="movements">
            <label>
                Search products (search by name, reference, supplier)
                <input name="q" value="{{ $q }}" placeholder="Search products (search by name, reference, supplier)">
            </label>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Search</button>
                @if(filled($q))
                    <a href="{{ route('admin.catalog.stock', ['tab' => 'movements']) }}" class="btn btn-ghost">Reset</a>
                @endif
            </div>
        </form>

        <details class="st-advanced">
            <summary>
                <span>Advanced filters</span>
                <span aria-hidden="true">▾</span>
            </summary>
            <div class="st-advanced-body" style="color:var(--ps-muted);font-size:0.88rem;">
                Use the search box to filter movements by product name, reference, supplier, or type.
            </div>
        </details>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Product name</th>
                    <th>Reference</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Date &amp; Time</th>
                    <th>Employee</th>
                </tr>
                </thead>
                <tbody>
                @forelse($movements as $movement)
                    @php
                        $delta = (float) $movement->quantity;
                        $badgeClass = $delta > 0 ? 'plus' : ($delta < 0 ? 'minus' : 'zero');
                        $typeLabel = match ($movement->type) {
                            'in' => 'Stock in',
                            'out' => 'Stock out',
                            default => 'Employee Edition',
                        };
                    @endphp
                    <tr>
                        <td>{{ $movement->id }}</td>
                        <td>
                            <div class="st-name-cell">
                                @if($movement->product?->image_url)
                                    <img src="{{ $movement->product->image_url }}" alt="" class="st-thumb">
                                @else
                                    <div class="st-thumb-ph">—</div>
                                @endif
                                <strong>{{ $movement->product?->name ?? '—' }}</strong>
                            </div>
                        </td>
                        <td>{{ $movement->product?->sku ?: '—' }}</td>
                        <td>{{ $typeLabel }}</td>
                        <td>
                            <span class="st-delta {{ $badgeClass }}">
                                {{ $delta > 0 ? '+ ' : ($delta < 0 ? '− ' : '') }}{{ number_format(abs($delta), 0) }}
                            </span>
                        </td>
                        <td>{{ $movement->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $movement->employee?->full_name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="color:var(--ps-muted);">No movements found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:0.85rem;">{{ $movements->links() }}</div>
    </div>
@endif
@endsection

@push('scripts')
@if($tab === 'stock')
<script>
(function () {
    const form = document.getElementById('stock-bulk-form');
    if (!form) return;
    const selectAll = document.getElementById('stock-select-all');
    const applyBtn = document.getElementById('bulk-apply');
    const countEl = document.getElementById('bulk-count');
    const qty = document.getElementById('bulk-qty');
    const rows = () => Array.from(document.querySelectorAll('.stock-row-check'));

    function sync() {
        const list = rows();
        const selected = list.filter((el) => el.checked);
        const n = selected.length;
        if (selectAll) {
            selectAll.checked = list.length > 0 && n === list.length;
            selectAll.indeterminate = n > 0 && n < list.length;
        }
        if (countEl) countEl.textContent = n + ' selected';
        if (applyBtn) applyBtn.disabled = n === 0 || !qty.value;
    }

    selectAll?.addEventListener('change', () => {
        rows().forEach((el) => { el.checked = selectAll.checked; });
        sync();
    });
    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('stock-row-check')) sync();
    });
    qty?.addEventListener('input', sync);
    form.addEventListener('submit', (e) => {
        const n = rows().filter((el) => el.checked).length;
        if (n === 0 || !qty.value) {
            e.preventDefault();
            alert(n === 0 ? 'Select at least one product.' : 'Enter a quantity change.');
        }
    });
    sync();
})();
</script>
@endif
@endpush
