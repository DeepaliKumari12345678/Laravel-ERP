@extends('admin.layouts.app')

@section('title', $customer->full_name)

@section('content')
@php
    $statusColors = [
        'pending' => '#fbbd3b',
        'processing' => '#25b9d7',
        'paid' => '#7cd2e5',
        'shipped' => '#3f7cac',
        'completed' => '#70b580',
        'cancelled' => '#6c868e',
    ];
    $statusLabels = [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'paid' => 'Payment accepted',
        'shipped' => 'Shipped',
        'completed' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];
@endphp

<style>
    .cv-layout { display:grid; grid-template-columns:minmax(0,2fr) minmax(260px,1fr); gap:1rem; align-items:start; }
    .cv-meta .stat-line span:first-child { color:var(--ps-muted); }
    .cv-badge-row { display:flex; flex-wrap:wrap; gap:0.35rem; margin-top:0.35rem; }
    .cv-order-stats { display:flex; gap:0.5rem; margin-bottom:0.85rem; }
    .cv-pill { padding:0.35rem 0.65rem; border-radius:3px; font-size:0.82rem; font-weight:600; }
    .cv-pill.ok { background:#e8f5eb; color:#3d8b4f; }
    .cv-pill.bad { background:#fde8e6; color:var(--danger); }
    .cv-alert { background:#eef7fb; border:1px solid #cfe8f1; color:#1e6f84; padding:0.65rem 0.75rem; border-radius:4px; font-size:0.82rem; margin-bottom:0.75rem; }
    @media (max-width:980px) { .cv-layout { grid-template-columns:1fr; } }
</style>

<div class="ps-breadcrumb"><a href="{{ route('admin.customers.index') }}">Customers</a> &gt; {{ $customer->full_name }}</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;display:flex;align-items:center;gap:0.5rem;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="10" r="3"/><path d="M6.5 18.2c1.4-2 3.3-3 5.5-3s4.1 1 5.5 3"/></svg>
        {{ $customer->full_name }}
    </h1>
    <div class="actions">
        <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-primary">✎ Edit customer</a>
        <form method="post" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer?')" style="display:inline;">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger" type="submit">Delete</button>
        </form>
        <a href="{{ route('admin.customers.index') }}" class="btn btn-ghost">Back to list</a>
    </div>
</div>

<div class="cv-layout">
    <div style="display:grid;gap:1rem;">
        <div class="card cv-meta">
            <div class="card-head">
                <h3 style="margin:0;">[{{ str_pad((string)$customer->id, 6, '0', STR_PAD_LEFT) }}] {{ $customer->email }}</h3>
                <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-ghost" style="padding:0.3rem 0.55rem;" title="Edit">✎</a>
            </div>
            <div class="stat-line"><span>Social title</span><strong>{{ $customer->social_title ?: '—' }}</strong></div>
            <div class="stat-line"><span>Age</span><strong>{{ $customer->age !== null ? $customer->age.' years' : '—' }}</strong></div>
            <div class="stat-line"><span>Registration date</span><strong>{{ $customer->created_at?->format('m/d/Y H:i:s') }}</strong></div>
            <div class="stat-line"><span>Phone</span><strong>{{ $customer->phone ?: '—' }}</strong></div>
            <div class="stat-line"><span>Company</span><strong>{{ $customer->company ?: '—' }}</strong></div>
            <div class="stat-line"><span>Type</span><strong>{{ ucfirst($customer->type) }}</strong></div>
            <div class="stat-line"><span>Group</span><strong>{{ $customer->group?->name ?? '—' }}@if((float) ($customer->group?->discount_percent ?? 0) > 0) ({{ number_format((float) $customer->group->discount_percent, 2) }}% discount)@endif</strong></div>
            <div class="stat-line"><span>Code</span><strong>{{ $customer->customer_code }}</strong></div>
            <div class="stat-line"><span>Total spent</span><strong>{{ number_format($totalSpent, 2) }} {{ $currency }}</strong></div>
            <div class="stat-line">
                <span>Registrations</span>
                <div class="cv-badge-row">
                    <span class="badge {{ $customer->newsletter ? 'badge-on' : 'badge-off' }}">Newsletter</span>
                    <span class="badge {{ $customer->partner_offers ? 'badge-on' : 'badge-off' }}">Partner offers</span>
                </div>
            </div>
            <div class="stat-line">
                <span>Status</span>
                <span class="badge {{ $customer->active ? 'badge-on' : 'badge-off' }}">{{ $customer->active ? 'Active' : 'Disabled' }}</span>
            </div>
            @if($customer->address || $customer->city || $customer->country)
                <div class="stat-line" style="align-items:start;">
                    <span>Address</span>
                    <strong style="text-align:right;">
                        {{ $customer->address }}<br>
                        {{ collect([$customer->postcode, $customer->city, $customer->state])->filter()->implode(' ') }}<br>
                        {{ $customer->country }}
                    </strong>
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-head">
                <h3 style="margin:0;display:flex;align-items:center;gap:0.4rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M6 7h15l-1.5 9h-12z"/><path d="M6 7 5 3H2"/><circle cx="9" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/></svg>
                    Orders [{{ $customer->orders->count() }}]
                </h3>
                <a href="{{ route('admin.orders.create') }}" class="btn btn-ghost" style="padding:0.25rem 0.55rem;" title="Add order">+</a>
            </div>

            <div class="cv-order-stats">
                <span class="cv-pill ok">Valid orders: {{ $validOrders }}</span>
                <span class="cv-pill bad">Invalid orders: {{ $invalidOrders }}</span>
            </div>

            <div style="overflow-x:auto;">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($customer->orders as $order)
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $order->payment_method ?: '—' }}</td>
                            <td>
                                <span class="badge" style="background:{{ $statusColors[$order->status] ?? '#dbe2e8' }};color:#fff;">
                                    {{ $statusLabels[$order->status] ?? ucfirst($order->status) }}
                                </span>
                            </td>
                            <td>{{ number_format((float)$order->total, 2) }} {{ $order->currency }}</td>
                            <td><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-ghost" style="padding:0.25rem 0.45rem;" title="View">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                            </a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="color:var(--ps-muted);">No records found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3 style="margin:0;">Addresses [{{ $customer->addresses->count() }}]</h3>
                <a href="{{ route('admin.addresses.create', ['customer_id' => $customer->id]) }}" class="btn btn-ghost" style="padding:0.25rem 0.55rem;">+</a>
            </div>
            @forelse($customer->addresses as $address)
                <div style="padding:0.65rem 0;border-bottom:1px solid #f0f2f4;display:flex;justify-content:space-between;gap:0.75rem;align-items:start;">
                    <div>
                        <strong>{{ $address->alias ?: 'Address' }}</strong>
                        @if($address->is_default)<span class="badge badge-core" style="margin-left:0.35rem;">Default</span>@endif
                        <div style="color:var(--ps-muted);font-size:0.86rem;margin-top:0.25rem;line-height:1.45;">
                            {{ $address->full_name }}<br>
                            {{ $address->address1 }}@if($address->address2), {{ $address->address2 }}@endif<br>
                            {{ collect([$address->postcode, $address->city])->filter()->implode(' ') }} {{ $address->country }}
                        </div>
                    </div>
                    <a href="{{ route('admin.addresses.edit', $address) }}" class="btn btn-ghost" style="padding:0.25rem 0.45rem;">✎</a>
                </div>
            @empty
                <p style="color:var(--ps-muted);margin:0;">No records found</p>
            @endforelse
        </div>
    </div>

    <div style="display:grid;gap:1rem;">
        <div class="card">
            <h3 style="margin-top:0;">Add a private note</h3>
            <div class="cv-alert">This note will be displayed to all employees but not to the customer.</div>
            <form method="post" action="{{ route('admin.customers.note', $customer) }}">
                @csrf
                @method('PUT')
                <textarea name="note" rows="4" placeholder="Write a private note…">{{ old('note', $customer->note) }}</textarea>
                <button class="btn btn-primary" type="submit" style="margin-top:0.65rem;">Save</button>
            </form>
        </div>
    </div>
</div>
@endsection
