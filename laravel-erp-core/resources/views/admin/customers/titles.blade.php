@extends('admin.layouts.app')

@section('title', 'Titles')

@section('content')
@php
    $filtersActive = collect(request()->only(['id', 'name', 'gender']))->filter(fn ($v) => filled($v))->isNotEmpty();
@endphp

<style>
    .cg-tabs { display:flex; gap:0; border-bottom:1px solid var(--ps-line); margin-bottom:1rem; }
    .cg-tabs a {
        padding:0.7rem 1.1rem; text-decoration:none; color:var(--ps-muted); font-weight:600;
        border-bottom:2px solid transparent; margin-bottom:-1px;
    }
    .cg-tabs a.active { color:var(--ps-ink); border-bottom-color:#25b9d7; }
    .tt-filters { display:flex; flex-wrap:wrap; gap:0.4rem; align-items:end; margin-bottom:0.85rem; }
    .tt-filters label { flex:1 1 120px; min-width:100px; }
    .tt-filters .filter-actions { display:flex; gap:0.4rem; flex:0 0 auto; }
    .bulk-bar { display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap; margin-bottom:0.75rem; }
    .bulk-bar select { width:auto; min-width:11rem; }
    .bulk-check {
        width:auto !important; min-width:1rem; height:1rem; margin:0; padding:0;
        accent-color:#25b9d7; cursor:pointer;
    }
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
    .tt-gender-icon {
        width:22px; height:22px; border-radius:50%; display:inline-grid; place-items:center;
        font-size:0.72rem; font-weight:700; color:#fff;
    }
    .tt-gender-icon.male { background:#70b580; }
    .tt-gender-icon.female { background:#e88aa4; }
    .tt-gender-icon.neutral { background:#8aa0ad; }
    .tt-thumb {
        width:28px; height:28px; object-fit:contain; border:1px solid var(--ps-line);
        border-radius:3px; background:#fff;
    }
</style>

<div class="ps-breadcrumb">Customer Settings &gt; Titles</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">Titles</h1>
    <div class="actions">
        <a href="{{ route('admin.customer-titles.create') }}" class="btn btn-primary">+ Add new title</a>
    </div>
</div>

<div class="cg-tabs">
    <a href="{{ route('admin.settings.group', ['group' => 'customers']) }}">Customer Settings</a>
    <a href="{{ route('admin.customer-groups.index') }}">Groups</a>
    <a href="{{ route('admin.customer-titles.index') }}" class="active">Titles</a>
</div>

<div class="card">
    <div class="card-head"><h3 style="margin:0;">Titles ({{ $titles->total() }})</h3></div>

    <form method="get" action="{{ route('admin.customer-titles.index') }}" class="tt-filters" data-auto-search="off">
        <label>ID<input type="number" name="id" value="{{ request('id') }}"></label>
        <label>Social title<input name="name" value="{{ request('name') }}"></label>
        <label>Gender
            <select name="gender">
                <option value="">—</option>
                <option value="male" @selected(request('gender') === 'male')>Male</option>
                <option value="female" @selected(request('gender') === 'female')>Female</option>
                <option value="neutral" @selected(request('gender') === 'neutral')>Neutral</option>
            </select>
        </label>
        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Search</button>
            @if($filtersActive)
                <a href="{{ route('admin.customer-titles.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    <form method="post" action="{{ route('admin.customer-titles.bulk') }}" id="titles-bulk-form" class="bulk-bar">
        @csrf
        <select name="action" id="titles-bulk-action" required>
            <option value="">Bulk actions</option>
            <option value="delete">Delete selected</option>
        </select>
        <button class="btn btn-primary" type="submit" id="titles-bulk-apply" disabled>Apply</button>
        <span class="team-muted" id="titles-bulk-count">0 item(s) selected</span>
    </form>

    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th style="width:2.25rem;">
                    <input type="checkbox" id="titles-select-all" class="bulk-check" title="Select all">
                </th>
                <th>ID</th>
                <th>Social title</th>
                <th>Gender</th>
                <th>Image</th>
                <th style="text-align:right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($titles as $title)
                <tr>
                    <td>
                        <input type="checkbox" form="titles-bulk-form" name="ids[]" value="{{ $title->id }}" class="titles-row-check bulk-check">
                    </td>
                    <td>{{ $title->id }}</td>
                    <td><strong>{{ $title->name }}</strong></td>
                    <td>
                        <span class="tt-gender-icon {{ $title->gender }}" title="{{ ucfirst($title->gender) }}">
                            {{ $title->gender === 'male' ? '♂' : ($title->gender === 'female' ? '♀' : '•') }}
                        </span>
                        {{ ucfirst($title->gender) }}
                    </td>
                    <td>
                        @if($title->image_url)
                            <img src="{{ $title->image_url }}" alt="" class="tt-thumb"
                                 style="width:{{ $title->image_width ?: 28 }}px;height:{{ $title->image_height ?: 28 }}px;">
                        @else
                            <span class="tt-gender-icon {{ $title->gender }}">
                                {{ $title->gender === 'male' ? '♂' : ($title->gender === 'female' ? '♀' : '•') }}
                            </span>
                        @endif
                    </td>
                    <td>
                        <div class="cu-actions">
                            <a href="{{ route('admin.customer-titles.edit', $title) }}" class="cu-icon-btn" title="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </a>
                            <div class="cu-menu">
                                <button type="button" class="cu-icon-btn cu-menu-toggle" title="More">⋮</button>
                                <div class="cu-menu-panel">
                                    <form method="post" action="{{ route('admin.customer-titles.destroy', $title) }}" onsubmit="return confirm('Delete this title?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" style="color:var(--danger);">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="color:var(--ps-muted);">No titles found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:0.85rem;">{{ $titles->links() }}</div>
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
    const form = document.getElementById('titles-bulk-form');
    if (!form) return;
    const selectAll = document.getElementById('titles-select-all');
    const action = document.getElementById('titles-bulk-action');
    const applyBtn = document.getElementById('titles-bulk-apply');
    const countEl = document.getElementById('titles-bulk-count');
    const rows = () => Array.from(document.querySelectorAll('.titles-row-check'));

    function sync() {
        const list = rows();
        const selected = list.filter((el) => el.checked);
        const n = selected.length;
        if (selectAll) {
            selectAll.checked = list.length > 0 && n === list.length;
            selectAll.indeterminate = n > 0 && n < list.length;
        }
        if (countEl) countEl.textContent = n + ' item(s) selected';
        if (applyBtn) applyBtn.disabled = n === 0 || !action.value;
    }

    selectAll?.addEventListener('change', () => {
        rows().forEach((el) => { el.checked = selectAll.checked; });
        sync();
    });
    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('titles-row-check') || e.target === action) sync();
    });
    form.addEventListener('submit', (e) => {
        const n = rows().filter((el) => el.checked).length;
        if (n === 0 || !action.value) {
            e.preventDefault();
            return;
        }
        if (action.value === 'delete' && !confirm('Delete ' + n + ' selected title(s)?')) e.preventDefault();
    });
    sync();
})();
</script>
@endpush
