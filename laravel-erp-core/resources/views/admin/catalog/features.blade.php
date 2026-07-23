@extends('admin.layouts.app')

@section('title', 'Features')

@section('content')
@php
    $filtersActive = collect(request()->only(['id', 'name']))->filter(fn ($v) => filled($v))->isNotEmpty();
@endphp

<style>
    .af-tabs { display:flex; gap:0; border-bottom:1px solid var(--ps-line); margin-bottom:1rem; }
    .af-tabs a {
        padding:0.7rem 1.1rem; text-decoration:none; color:var(--ps-muted); font-weight:600;
        border-bottom:2px solid transparent; margin-bottom:-1px;
    }
    .af-tabs a.active { color:var(--ps-ink); border-bottom-color:#25b9d7; }
    .af-info {
        background:#e8f7fb; border:1px solid #b9e4ef; border-radius:4px;
        padding:0.95rem 1.1rem; font-size:0.9rem; color:#1e6475; margin-bottom:1rem; line-height:1.5;
        max-width: 52rem;
    }
    .list-filters { display:flex; flex-wrap:wrap; gap:0.4rem; align-items:end; margin-bottom:0.85rem; }
    .list-filters label { flex:1 1 110px; min-width:90px; }
    .list-filters .filter-actions { display:flex; gap:0.4rem; flex:0 0 auto; }
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
    .name-link { color: var(--ps-blue-dark); text-decoration: none; font-weight: 600; }
    .name-link:hover { text-decoration: underline; }
</style>

<div class="ps-breadcrumb">Attributes &amp; Features &gt; Features</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">Features</h1>
    <div class="actions" style="display:flex;gap:0.5rem;flex-wrap:wrap;">
        <a href="{{ route('admin.catalog.features.create') }}" class="btn btn-primary">+ Add new feature</a>
        <a href="{{ route('admin.catalog.feature-values.create') }}" class="btn btn-primary">+ Add new feature value</a>
    </div>
</div>

<div class="af-tabs">
    <a href="{{ route('admin.catalog.attributes') }}">Attributes</a>
    <a href="{{ route('admin.catalog.features') }}" class="active">Features</a>
</div>

<div class="af-info">
    Features are a product’s fixed characteristics — for example composition stays the same, unlike size or color.
    Assign them on the product page under the Features tab.
</div>

<div class="card">
    <div class="card-head"><h3 style="margin:0;">Features ({{ $features->total() }})</h3></div>

    <form method="get" action="{{ route('admin.catalog.features') }}" class="list-filters" data-auto-search="off">
        <label>ID<input type="number" name="id" value="{{ request('id') }}"></label>
        <label>Name<input name="name" value="{{ request('name') }}"></label>
        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Search</button>
            @if($filtersActive)
                <a href="{{ route('admin.catalog.features') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th style="width:4rem;">ID</th>
                <th>Name</th>
                <th>Values</th>
                <th>Position</th>
                <th style="width:7rem;"></th>
            </tr>
            </thead>
            <tbody>
            @forelse($features as $feature)
                <tr>
                    <td>{{ $feature->id }}</td>
                    <td><a class="name-link" href="{{ route('admin.catalog.features.show', $feature) }}">{{ $feature->name }}</a></td>
                    <td>{{ $feature->values_count }}</td>
                    <td>{{ $feature->position }}</td>
                    <td>
                        <div class="cu-actions">
                            <a class="cu-icon-btn" href="{{ route('admin.catalog.features.show', $feature) }}" title="View values">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                            </a>
                            <div class="cu-menu">
                                <button type="button" class="cu-icon-btn" data-menu-toggle title="More">⋮</button>
                                <div class="cu-menu-panel">
                                    <a href="{{ route('admin.catalog.features.edit', $feature) }}">Edit</a>
                                    <form method="post" action="{{ route('admin.catalog.features.destroy', $feature) }}" onsubmit="return confirm('Delete this feature and all its values?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="team-muted" style="text-align:center;padding:1.5rem;">No features yet. Create Composition, Material, etc.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1rem;">{{ $features->links() }}</div>
</div>

<script>
document.querySelectorAll('[data-menu-toggle]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const menu = btn.closest('.cu-menu');
        document.querySelectorAll('.cu-menu.open').forEach((m) => { if (m !== menu) m.classList.remove('open'); });
        menu.classList.toggle('open');
    });
});
document.addEventListener('click', () => document.querySelectorAll('.cu-menu.open').forEach((m) => m.classList.remove('open')));
</script>
@endsection
