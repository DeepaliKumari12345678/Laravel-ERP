@extends('admin.layouts.app')

@section('title', 'Payment methods')

@section('content')
<style>
    .pm-info {
        background:#e8f7fb; border:1px solid #b9e4ef; border-radius:4px;
        padding:0.95rem 1.1rem; font-size:0.9rem; color:#1e6475; margin-bottom:1rem; line-height:1.5;
        max-width: 52rem;
    }
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
        display:flex; width:100%; padding:0.45rem 0.75rem; background:none; border:0;
        font:inherit; color:var(--ps-ink); text-align:left; cursor:pointer; text-decoration:none;
    }
    .cu-menu-panel a:hover, .cu-menu-panel button:hover { background:#f3f5f6; }
    .pm-filters {
        display:grid; grid-template-columns:80px 1.2fr 1fr 140px auto auto;
        gap:.45rem; align-items:end; padding:.8rem 1rem; background:#fbfcfc;
        border-bottom:1px solid var(--ps-line);
    }
    @media (max-width:720px) {
        .pm-filters { grid-template-columns:1fr 1fr; }
    }
</style>

<div class="ps-breadcrumb">Improve &gt; Payment &gt; Payment methods</div>

<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <div>
        <h1 class="page-title" style="margin:0;">Payment methods</h1>
        <p class="page-sub" style="margin:.25rem 0 0;">Methods available when creating or paying for orders.</p>
    </div>
    <a href="{{ route('admin.payment.methods.create') }}" class="btn btn-primary">+ Add payment method</a>
</div>

<div class="pm-info">
    Create cash, bank transfer, card gateway, or other methods your shop accepts.
    Only enabled methods appear on new orders.
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <div class="card-head" style="padding:1rem;"><h3 style="margin:0;">Payment methods ({{ $methods->total() }})</h3></div>

    <form method="get" action="{{ route('admin.payment.methods') }}" class="pm-filters" data-auto-search="off">
        <label>ID<input type="number" name="id" value="{{ request('id') }}"></label>
        <label>Name<input name="name" value="{{ request('name') }}" placeholder="Search name"></label>
        <label>Code<input name="code" value="{{ request('code') }}" placeholder="e.g. cod"></label>
        <label>Enabled
            <select name="active">
                <option value="">All</option>
                <option value="1" @selected(request('active') === '1')>Yes</option>
                <option value="0" @selected(request('active') === '0')>No</option>
            </select>
        </label>
        <button class="btn btn-primary" type="submit">Search</button>
        @if(collect(request()->except('page'))->filter(fn ($v) => filled($v))->isNotEmpty())
            <a class="btn btn-ghost" href="{{ route('admin.payment.methods') }}">Reset</a>
        @endif
    </form>

    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Code</th>
                <th>Description</th>
                <th>Position</th>
                <th>Enabled</th>
                <th style="width:6rem;"></th>
            </tr>
            </thead>
            <tbody>
            @forelse($methods as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td><strong>{{ $item->name }}</strong></td>
                    <td><code>{{ $item->code }}</code></td>
                    <td style="color:var(--ps-muted);max-width:18rem;">{{ $item->description ?: '—' }}</td>
                    <td>{{ $item->position }}</td>
                    <td>
                        <form method="post" action="{{ route('admin.payment.methods.toggle', $item) }}">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="cu-toggle {{ $item->active ? 'on' : 'off' }}">
                                {{ $item->active ? 'Yes' : 'No' }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="cu-actions">
                            <a class="cu-icon-btn" href="{{ route('admin.payment.methods.edit', $item) }}" title="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </a>
                            <div class="cu-menu">
                                <button type="button" class="cu-icon-btn" data-menu-toggle title="More">⋮</button>
                                <div class="cu-menu-panel">
                                    <a href="{{ route('admin.payment.methods.edit', $item) }}">Edit</a>
                                    <form method="post" action="{{ route('admin.payment.methods.destroy', $item) }}" onsubmit="return confirm('Delete this payment method?')">
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
                <tr><td colspan="7" class="team-muted" style="text-align:center;padding:1.5rem;color:var(--ps-muted);">No payment methods found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:1rem;">{{ $methods->links() }}</div>
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
