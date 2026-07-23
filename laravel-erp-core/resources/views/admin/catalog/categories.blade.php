@extends('admin.layouts.app')

@section('title', isset($currentCategory) && $currentCategory ? 'Category '.$currentCategory->name : 'Categories')

@section('content')
@php
    $filtersActive = collect(request()->only(['id', 'name', 'description', 'position', 'active']))
        ->filter(fn ($v) => filled($v))
        ->isNotEmpty();
    $parentId = $currentCategory?->id;
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
        box-shadow:0 6px 18px rgba(0,0,0,.08); min-width:140px; padding:0.25rem 0;
    }
    .cu-menu.open .cu-menu-panel { display:block; }
    .cu-menu-panel a, .cu-menu-panel button {
        display:flex; align-items:center; gap:0.45rem; width:100%;
        padding:0.45rem 0.75rem; background:none; border:0; font:inherit; color:var(--ps-ink);
        text-align:left; cursor:pointer;
    }
    .cu-menu-panel a:hover, .cu-menu-panel button:hover { background:#f3f5f6; }
    .cat-filters { display:flex; flex-wrap:wrap; gap:0.4rem; align-items:end; margin-bottom:0.85rem; }
    .cat-filters label { flex:1 1 110px; min-width:90px; }
    .cat-filters .filter-actions { display:flex; gap:0.4rem; flex:0 0 auto; }
    .bulk-bar { display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap; margin-bottom:0.75rem; }
    .bulk-bar select { width:auto; min-width:11rem; }
    .bulk-check {
        width:auto !important; min-width:1rem; height:1rem; margin:0; padding:0;
        accent-color:#25b9d7; cursor:pointer;
    }
    .cat-name-link { color: var(--ps-blue-dark); text-decoration: none; font-weight: 600; }
    .cat-name-link:hover { text-decoration: underline; }
    .cat-trail { color: var(--ps-muted); font-size: 0.85rem; margin-top: 0.25rem; }
    .cat-trail a { color: var(--ps-blue-dark); }
</style>

<div class="ps-breadcrumb">
    <a href="{{ route('admin.catalog.categories') }}">Catalog</a> &gt; Categories
    @if($currentCategory)
        &gt; {{ $currentCategory->name }}
    @endif
</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <div>
        <h1 class="page-title" style="margin:0;">
            {{ $currentCategory ? 'Category '.$currentCategory->name : 'Categories' }}
        </h1>
        @if($currentCategory)
            <div class="cat-trail">
                <a href="{{ route('admin.catalog.categories') }}">Home</a>
                @if($currentCategory->parent)
                    &gt;
                    <a href="{{ route('admin.catalog.categories', ['parent_id' => $currentCategory->parent_id]) }}">{{ $currentCategory->parent->name }}</a>
                @endif
                &gt; {{ $currentCategory->name }}
            </div>
        @endif
    </div>
    <div class="actions">
        @if($currentCategory)
            <a href="{{ route('admin.catalog.categories.edit', $currentCategory) }}" class="btn btn-ghost">Edit category</a>
        @endif
        <a href="{{ route('admin.catalog.categories.create', $parentId ? ['parent_id' => $parentId] : []) }}" class="btn btn-primary">+ Add new category</a>
    </div>
</div>

<div class="kpi-row" style="grid-template-columns:repeat(4,minmax(0,1fr));">
    <div class="kpi">
        <div class="label">Disabled categories</div>
        <div class="value">{{ $kpis['disabled'] }}</div>
    </div>
    <div class="kpi">
        <div class="label">Empty categories</div>
        <div class="value">{{ $kpis['empty'] }}</div>
    </div>
    <div class="kpi">
        <div class="label">Top category</div>
        <div class="value" style="font-size:1rem;">{{ $kpis['top_name'] }}</div>
    </div>
    <div class="kpi active">
        <div class="label">Avg products / category</div>
        <div class="value">{{ $kpis['avg_products'] }}</div>
    </div>
</div>

<div class="card" style="margin-top:1rem;">
    <div class="card-head">
        <h3>Categories ({{ $categories->total() }})</h3>
    </div>

    <form method="get" action="{{ route('admin.catalog.categories') }}" class="cat-filters" data-auto-search="off">
        @if($parentId)
            <input type="hidden" name="parent_id" value="{{ $parentId }}">
        @endif
        <label>ID<input type="number" name="id" value="{{ request('id') }}"></label>
        <label>Name<input name="name" value="{{ request('name') }}"></label>
        <label>Description<input name="description" value="{{ request('description') }}"></label>
        <label>Position<input type="number" name="position" value="{{ request('position') }}"></label>
        <label>Displayed
            <select name="active">
                <option value="">All</option>
                <option value="1" @selected(request('active') === '1')>Yes</option>
                <option value="0" @selected(request('active') === '0')>No</option>
            </select>
        </label>
        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Search</button>
            @if($filtersActive)
                <a href="{{ route('admin.catalog.categories', $parentId ? ['parent_id' => $parentId] : []) }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    <form method="post" action="{{ route('admin.catalog.categories.bulk') }}" id="categories-bulk-form" class="bulk-bar">
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
                <th>Name</th>
                <th>Description</th>
                <th>Products</th>
                <th>Position</th>
                <th>Displayed</th>
                <th style="text-align:right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($categories as $category)
                @php($hasChildren = (int) $category->children_count > 0)
                <tr>
                    <td>
                        <input type="checkbox" form="categories-bulk-form" name="ids[]" value="{{ $category->id }}" class="bulk-row-check bulk-check" aria-label="Select category {{ $category->id }}">
                    </td>
                    <td>{{ $category->id }}</td>
                    <td>
                        @if($hasChildren)
                            <a class="cat-name-link" href="{{ route('admin.catalog.categories', ['parent_id' => $category->id]) }}">{{ $category->name }}</a>
                            <div style="color:var(--ps-muted);font-size:.76rem;">{{ $category->children_count }} subcategor{{ $category->children_count === 1 ? 'y' : 'ies' }}</div>
                        @else
                            <strong>{{ $category->name }}</strong>
                        @endif
                    </td>
                    <td style="max-width:240px;">{{ \Illuminate\Support\Str::limit($category->description, 80) ?: '—' }}</td>
                    <td>{{ $category->products_count }}</td>
                    <td>{{ $category->position }}</td>
                    <td>
                        <form method="post" action="{{ route('admin.catalog.categories.toggle', $category) }}">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="cu-toggle {{ $category->active ? 'on' : 'off' }}">{{ $category->active ? 'Yes' : 'No' }}</button>
                        </form>
                    </td>
                    <td>
                        <div class="cu-actions">
                            @if($hasChildren)
                                {{-- View / drill down --}}
                                <a href="{{ route('admin.catalog.categories', ['parent_id' => $category->id]) }}" class="cu-icon-btn" title="View subcategories">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                </a>
                            @else
                                {{-- Leaf: Edit --}}
                                <a href="{{ route('admin.catalog.categories.edit', $category) }}" class="cu-icon-btn" title="Edit">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                </a>
                            @endif
                            <div class="cu-menu">
                                <button type="button" class="cu-icon-btn cu-menu-toggle" title="More actions">⋮</button>
                                <div class="cu-menu-panel">
                                    @if($hasChildren)
                                        <a href="{{ route('admin.catalog.categories.edit', $category) }}">Edit</a>
                                    @endif
                                    <form method="post" action="{{ route('admin.catalog.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="color:var(--danger);">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" style="color:var(--ps-muted);">No categories found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:0.85rem;">{{ $categories->links() }}</div>
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
    const form = document.getElementById('categories-bulk-form');
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
        if (e.target.classList.contains('bulk-row-check') || e.target === action) syncBulkUi();
    });
    form.addEventListener('submit', (e) => {
        const n = rowChecks().filter((el) => el.checked).length;
        if (n === 0 || !action.value) {
            e.preventDefault();
            alert(n === 0 ? 'Please select at least one category.' : 'Please choose a bulk action.');
            return;
        }
        if (action.value === 'delete' && !confirm('Delete ' + n + ' selected categor' + (n === 1 ? 'y' : 'ies') + '?')) {
            e.preventDefault();
        }
    });
    syncBulkUi();
})();
</script>
@endpush
