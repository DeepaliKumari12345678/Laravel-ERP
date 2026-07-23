@extends('admin.layouts.app')

@section('title', 'Order statuses')

@section('content')
<style>
    .os-pill {
        display:inline-flex; align-items:center; gap:0.4rem;
        padding:0.25rem 0.65rem; border-radius:3px; color:#fff;
        font-size:0.78rem; font-weight:600; white-space:nowrap;
    }
    .os-flag { display:inline-block; margin:0.1rem 0.15rem 0.1rem 0; }
    .os-muted { color:var(--ps-muted); font-size:0.76rem; }
    .os-actions { display:flex; justify-content:flex-end; gap:0.35rem; }
    .os-icon-btn {
        display:inline-flex; align-items:center; justify-content:center;
        width:30px; height:30px; border:1px solid var(--ps-line); border-radius:4px;
        background:#fff; color:var(--ps-ink); cursor:pointer;
    }
    .os-icon-btn:hover { border-color:var(--ps-blue); color:var(--ps-blue); }
</style>

<div class="ps-breadcrumb">Shop Parameters &gt; Order statuses</div>

<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <div>
        <h1 class="page-title" style="margin:0;">Order statuses</h1>
        <p class="page-sub" style="margin:0.25rem 0 0;">Control order workflow, customer notifications, paid orders, shipping and reporting.</p>
    </div>
    <a href="{{ route('admin.order-statuses.create') }}" class="btn btn-primary">+ Add new status</a>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <div class="card-head" style="padding:1rem;">
        <h3 style="margin:0;">Statuses ({{ $statuses->total() }})</h3>
    </div>

    <form method="get" action="{{ route('admin.order-statuses.index') }}" style="display:grid;grid-template-columns:1fr 180px auto auto;gap:0.45rem;align-items:end;padding:0.8rem 1rem;background:#fafbfc;border-bottom:1px solid var(--ps-line);">
        <label>Name<input name="name" value="{{ request('name') }}" placeholder="Search status"></label>
        <label>Active
            <select name="active">
                <option value="">All</option>
                <option value="1" @selected(request('active') === '1')>Yes</option>
                <option value="0" @selected(request('active') === '0')>No</option>
            </select>
        </label>
        <button class="btn btn-primary" type="submit">Search</button>
        @if(collect(request()->except('page'))->filter(fn ($v) => filled($v))->isNotEmpty())
            <a class="btn btn-ghost" href="{{ route('admin.order-statuses.index') }}">Reset</a>
        @endif
    </form>

    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Status</th>
                <th>Code</th>
                <th>Workflow behavior</th>
                <th>Email customer</th>
                <th>Orders</th>
                <th>Active</th>
                <th>Position</th>
                <th style="text-align:right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($statuses as $status)
                <tr>
                    <td>{{ $status->id }}</td>
                    <td><span class="os-pill" style="background:{{ $status->color }};">{{ $status->name }}</span></td>
                    <td><span class="os-muted">{{ $status->code }}</span></td>
                    <td>
                        @php
                            $flags = collect([
                                'is_paid' => 'Paid',
                                'is_shipped' => 'Shipped',
                                'is_delivered' => 'Delivered',
                                'is_cancelled' => 'Cancelled',
                                'counts_as_validated' => 'Validated sale',
                            ])->filter(fn ($label, $field) => $status->{$field});
                        @endphp
                        @forelse($flags as $label)
                            <span class="badge badge-on os-flag">{{ $label }}</span>
                        @empty
                            <span class="os-muted">—</span>
                        @endforelse
                    </td>
                    <td>
                        <span class="badge {{ $status->send_email ? 'badge-on' : 'badge-off' }}">{{ $status->send_email ? 'Yes' : 'No' }}</span>
                    </td>
                    <td>{{ $status->orders_count }}</td>
                    <td>
                        <span class="badge {{ $status->active ? 'badge-on' : 'badge-off' }}">{{ $status->active ? 'Enabled' : 'Disabled' }}</span>
                    </td>
                    <td>{{ $status->position }}</td>
                    <td>
                        <div class="os-actions">
                            <a class="os-icon-btn" href="{{ route('admin.order-statuses.edit', $status) }}" title="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </a>
                            @if($status->orders_count === 0)
                                <form method="post" action="{{ route('admin.order-statuses.destroy', $status) }}" onsubmit="return confirm('Delete this status?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="os-icon-btn" type="submit" title="Delete" style="color:var(--danger);">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" style="color:var(--ps-muted);">No statuses found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:1rem;">{{ $statuses->links() }}</div>
</div>
@endsection
