@extends('admin.layouts.app')

@section('title', $feature->name)

@section('content')
@php
    $filtersActive = collect(request()->only(['id', 'value']))->filter(fn ($v) => filled($v))->isNotEmpty();
@endphp

<style>
    .af-tabs { display:flex; gap:0; border-bottom:1px solid var(--ps-line); margin-bottom:1rem; }
    .af-tabs a {
        padding:0.7rem 1.1rem; text-decoration:none; color:var(--ps-muted); font-weight:600;
        border-bottom:2px solid transparent; margin-bottom:-1px;
    }
    .af-tabs a.active { color:var(--ps-ink); border-bottom-color:#25b9d7; }
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
</style>

<div class="ps-breadcrumb">Attributes &amp; Features &gt; Features</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">{{ $feature->name }}</h1>
    <div class="actions">
        <a href="{{ route('admin.catalog.features.values.create', $feature) }}" class="btn btn-primary">+ Add new feature value</a>
    </div>
</div>

<div class="af-tabs">
    <a href="{{ route('admin.catalog.attributes') }}">Attributes</a>
    <a href="{{ route('admin.catalog.features') }}" class="active">Features</a>
</div>

<div class="card">
    <div class="card-head"><h3 style="margin:0;">{{ $feature->name }} ({{ $values->total() }})</h3></div>

    <form method="get" action="{{ route('admin.catalog.features.show', $feature) }}" class="list-filters" data-auto-search="off">
        <label>ID<input type="number" name="id" value="{{ request('id') }}"></label>
        <label>Value<input name="value" value="{{ request('value') }}"></label>
        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Search</button>
            @if($filtersActive)
                <a href="{{ route('admin.catalog.features.show', $feature) }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th style="width:4rem;">ID</th>
                <th>Value</th>
                <th>Position</th>
                <th style="width:6rem;"></th>
            </tr>
            </thead>
            <tbody>
            @forelse($values as $value)
                <tr>
                    <td>{{ $value->id }}</td>
                    <td>{{ $value->value }}</td>
                    <td>{{ $value->position }}</td>
                    <td>
                        <div class="cu-actions">
                            <a class="cu-icon-btn" href="{{ route('admin.catalog.features.values.edit', [$feature, $value]) }}" title="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </a>
                            <div class="cu-menu">
                                <button type="button" class="cu-icon-btn" data-menu-toggle title="More">⋮</button>
                                <div class="cu-menu-panel">
                                    <form method="post" action="{{ route('admin.catalog.features.values.destroy', [$feature, $value]) }}" onsubmit="return confirm('Delete this value?')">
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
                <tr><td colspan="4" class="team-muted" style="text-align:center;padding:1.5rem;">No values yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-top:1rem;">
        <a href="{{ route('admin.catalog.features') }}" class="btn btn-ghost">← Back to list</a>
        <div>{{ $values->links() }}</div>
    </div>
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
