@extends('admin.layouts.app')

@section('title', 'Groups')

@section('content')
@php
    $filtersActive = collect(request()->only(['id', 'name', 'discount_percent', 'members', 'show_prices', 'created_from', 'created_to']))
        ->filter(fn ($v) => filled($v))
        ->isNotEmpty();
@endphp

<style>
    .cg-tabs { display:flex; gap:0; border-bottom:1px solid var(--ps-line); margin-bottom:1rem; }
    .cg-tabs a {
        padding:0.7rem 1.1rem; text-decoration:none; color:var(--ps-muted); font-weight:600;
        border-bottom:2px solid transparent; margin-bottom:-1px;
    }
    .cg-tabs a.active { color:var(--ps-ink); border-bottom-color:#25b9d7; }
    .cg-info {
        background:#e8f7fb; border:1px solid #b9e4ef; border-radius:4px;
        padding:0.85rem 1rem; font-size:0.88rem; color:#1e6475; margin-bottom:1rem; line-height:1.45;
    }
    .cg-filters {
        display:flex; flex-wrap:wrap; gap:0.4rem; align-items:end; margin-bottom:0.85rem;
    }
    .cg-filters label { flex:1 1 110px; min-width:90px; }
    .cg-filters .filter-actions { display:flex; gap:0.4rem; flex:0 0 auto; }
    .cu-actions { display:flex; gap:0.3rem; align-items:center; justify-content:flex-end; }
    .cu-icon-btn {
        width:auto; min-width:30px; height:30px; padding:0 0.65rem; border:1px solid var(--ps-line); border-radius:3px;
        background:#fff; display:inline-flex; align-items:center; gap:0.35rem; color:var(--ps-ink); cursor:pointer;
        text-decoration:none; font:inherit; font-size:0.82rem;
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

<div class="ps-breadcrumb">Customer Settings &gt; Groups</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">Groups</h1>
    <div class="actions">
        <a href="{{ route('admin.customer-groups.create') }}" class="btn btn-primary">+ Add new group</a>
    </div>
</div>

<div class="cg-tabs">
    <a href="{{ route('admin.settings.group', ['group' => 'customers']) }}">Customer Settings</a>
    <a href="{{ route('admin.customer-groups.index') }}" class="active">Groups</a>
    <a href="{{ route('admin.customer-titles.index') }}">Titles</a>
</div>

<div class="cg-info">
    This ERP includes three default customer groups:<br>
    <strong>Visitor</strong> — browsing without an account ·
    <strong>Guest</strong> — placing an order without registering ·
    <strong>Customer</strong> — creating an account.
</div>

<div class="card">
    <div class="card-head"><h3 style="margin:0;">Groups ({{ $groups->total() }})</h3></div>

    <form method="get" action="{{ route('admin.customer-groups.index') }}" class="cg-filters" data-auto-search="off">
        <label>ID<input type="number" name="id" value="{{ request('id') }}"></label>
        <label>Group name<input name="name" value="{{ request('name') }}"></label>
        <label>Discount (%)<input type="number" step="0.01" name="discount_percent" value="{{ request('discount_percent') }}"></label>
        <label>Members<input type="number" name="members" value="{{ request('members') }}"></label>
        <label>Show prices
            <select name="show_prices">
                <option value="">—</option>
                <option value="1" @selected(request('show_prices') === '1')>Yes</option>
                <option value="0" @selected(request('show_prices') === '0')>No</option>
            </select>
        </label>
        <label>From<input type="date" name="created_from" value="{{ request('created_from') }}"></label>
        <label>To<input type="date" name="created_to" value="{{ request('created_to') }}"></label>
        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Search</button>
            @if($filtersActive)
                <a href="{{ route('admin.customer-groups.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Group name</th>
                <th>Discount (%)</th>
                <th>Members</th>
                <th>Show prices</th>
                <th>Creation date</th>
                <th style="text-align:right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($groups as $group)
                <tr>
                    <td>{{ $group->id }}</td>
                    <td><strong>{{ $group->name }}</strong></td>
                    <td>{{ number_format((float) $group->discount_percent, 2) }}%</td>
                    <td>{{ $group->customers_count }}</td>
                    <td>{{ $group->show_prices ? 'Yes' : 'No' }}</td>
                    <td>{{ $group->created_at?->format('Y-m-d') }}</td>
                    <td>
                        <div class="cu-actions">
                            <a href="{{ route('admin.customer-groups.edit', $group) }}" class="cu-icon-btn" title="Edit">
                                Edit
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </a>
                            @unless($group->is_system)
                                <div class="cu-menu">
                                    <button type="button" class="cu-icon-btn cu-menu-toggle" title="More">▾</button>
                                    <div class="cu-menu-panel">
                                        <form method="post" action="{{ route('admin.customer-groups.destroy', $group) }}" onsubmit="return confirm('Delete this group?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" style="color:var(--danger);" @disabled($group->customers_count > 0)>Delete</button>
                                        </form>
                                    </div>
                                </div>
                            @endunless
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="color:var(--ps-muted);">No groups found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:0.85rem;">{{ $groups->links() }}</div>
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
</script>
@endpush
