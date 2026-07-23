@extends('admin.layouts.app')

@section('title', 'Customers')

@section('content')
<style>
    .cu-toggle {
        display:inline-flex; align-items:center; justify-content:center;
        min-width:42px; height:22px; border-radius:11px; border:0; cursor:pointer;
        font-size:0.68rem; font-weight:700; color:#fff; padding:0 0.4rem;
        font-family:inherit;
    }
    .cu-toggle.on { background:#70b580; }
    .cu-toggle.off { background:#bbcdd2; }
    .cu-sales { background:#e8f5eb; color:#3d8b4f; padding:0.15rem 0.45rem; border-radius:3px; font-weight:600; font-size:0.8rem; }
    .cu-actions { display:flex; gap:0.3rem; align-items:center; }
    .cu-icon-btn {
        width:30px; height:30px; border:1px solid var(--ps-line); border-radius:3px;
        background:#fff; display:inline-grid; place-items:center; color:var(--ps-ink); cursor:pointer;
    }
    .cu-icon-btn:hover { border-color:var(--ps-blue); color:var(--ps-blue-dark); }
    .cu-icon-btn.danger:hover { border-color:var(--danger); color:var(--danger); }
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
</style>

<div class="ps-breadcrumb">Sell &gt; Customers</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">Customers</h1>
    <div class="actions">
        <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">+ Add new customer</a>
    </div>
</div>

<div class="kpi-row" style="grid-template-columns:repeat(4,minmax(0,1fr));">
    <div class="kpi">
        <div class="label">Total customers</div>
        <div class="value">{{ $kpis['total'] }}</div>
        <div class="label" style="margin-top:0.25rem;">{{ $kpis['active'] }} active</div>
    </div>
    <div class="kpi">
        <div class="label">With orders</div>
        <div class="value">{{ $kpis['with_orders'] }}</div>
    </div>
    <div class="kpi">
        <div class="label">Orders per customer</div>
        <div class="value">{{ $kpis['avg_orders'] }}</div>
    </div>
    <div class="kpi active">
        <div class="label">Newsletter</div>
        <div class="value">{{ $kpis['newsletter'] }}</div>
    </div>
</div>

<div class="card" style="margin-top:1rem;">
    <div class="card-head">
        <h3>Customers ({{ $customers->total() }})</h3>
    </div>

    <form method="get" action="{{ route('admin.customers.index') }}" style="margin-bottom:0.85rem;">
        <div style="display:grid;grid-template-columns:70px 1fr 1fr 1.3fr 110px 130px 110px 130px 130px auto auto;gap:0.4rem;align-items:end;">
            <label>ID<input type="number" name="id" value="{{ request('id') }}"></label>
            <label>First name<input name="first_name" value="{{ request('first_name') }}"></label>
            <label>Last name<input name="last_name" value="{{ request('last_name') }}"></label>
            <label>Email<input name="email" value="{{ request('email') }}"></label>
            <label>Type
                <select name="type">
                    <option value="">—</option>
                    <option value="individual" @selected(request('type')==='individual')>Individual</option>
                    <option value="company" @selected(request('type')==='company')>Company</option>
                </select>
            </label>
            <label>Group
                <select name="customer_group_id">
                    <option value="">All</option>
                    @foreach($groups as $group)<option value="{{ $group->id }}" @selected((string) request('customer_group_id') === (string) $group->id)>{{ $group->name }}</option>@endforeach
                </select>
            </label>
            <label>Enabled
                <select name="active">
                    <option value="">All</option>
                    <option value="1" @selected(request('active')==='1')>Yes</option>
                    <option value="0" @selected(request('active')==='0')>No</option>
                </select>
            </label>
            <label>From<input type="date" name="date_from" value="{{ request('date_from') }}"></label>
            <label>To<input type="date" name="date_to" value="{{ request('date_to') }}"></label>
            <button class="btn btn-primary" type="submit">Search</button>
            @if(collect(request()->except('page'))->filter(fn ($v) => filled($v))->isNotEmpty())
                <a href="{{ route('admin.customers.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>First name</th>
                <th>Last name</th>
                <th>Email</th>
                <th>Type</th>
                <th>Group</th>
                <th>Sales</th>
                <th>Enabled</th>
                <th>Newsletter</th>
                <th>Registration</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($customers as $customer)
                @php $spent = (float) ($customer->sales_total ?? 0); @endphp
                <tr>
                    <td>{{ $customer->id }}</td>
                    <td>{{ $customer->social_title ?: '—' }}</td>
                    <td><a href="{{ route('admin.customers.show', $customer) }}" style="color:var(--ps-blue-dark);font-weight:600;">{{ $customer->first_name }}</a></td>
                    <td>{{ $customer->last_name ?: '—' }}</td>
                    <td>{{ $customer->email ?: '—' }}</td>
                    <td>{{ ucfirst($customer->type) }}</td>
                    <td>{{ $customer->group?->name ?? '—' }}</td>
                    <td>
                        @if($spent > 0)
                            <span class="cu-sales">{{ number_format($spent, 2) }} {{ $currency }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <form method="post" action="{{ route('admin.customers.toggle', $customer) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="field" value="active">
                            <button type="submit" class="cu-toggle {{ $customer->active ? 'on' : 'off' }}">{{ $customer->active ? 'Yes' : 'No' }}</button>
                        </form>
                    </td>
                    <td>
                        <form method="post" action="{{ route('admin.customers.toggle', $customer) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="field" value="newsletter">
                            <button type="submit" class="cu-toggle {{ $customer->newsletter ? 'on' : 'off' }}">{{ $customer->newsletter ? 'Yes' : 'No' }}</button>
                        </form>
                    </td>
                    <td>{{ $customer->created_at?->format('Y-m-d') }}</td>
                    <td>
                        <div class="cu-actions">
                            <a href="{{ route('admin.customers.edit', $customer) }}" class="cu-icon-btn" title="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </a>
                            <div class="cu-menu">
                                <button type="button" class="cu-icon-btn cu-menu-toggle" title="More">⋮</button>
                                <div class="cu-menu-panel">
                                    <a href="{{ route('admin.customers.show', $customer) }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                                        View
                                    </a>
                                    <form method="post" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="color:var(--danger);">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="12" style="color:var(--ps-muted);">No customers found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:0.85rem;">{{ $customers->links() }}</div>
</div>

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
@endsection
