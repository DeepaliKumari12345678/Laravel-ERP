@extends('admin.layouts.app')

@section('title', 'Orders')

@section('content')
@php
    $statusMap = $statuses->keyBy('code');
@endphp

<div class="ps-breadcrumb">Sell &gt; Orders</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">Orders</h1>
    <div class="actions">
        <a href="{{ route('admin.orders.create') }}" class="btn btn-primary">+ Add new order</a>
    </div>
</div>

<div class="kpi-row" style="grid-template-columns:repeat(4,minmax(0,1fr));">
    <div class="kpi">
        <div class="label">Conversion rate</div>
        <div class="value">{{ $kpis['conversion'] }}%</div>
        <div class="label" style="margin-top:0.25rem;">vs customers</div>
    </div>
    <div class="kpi">
        <div class="label">Pending orders</div>
        <div class="value">{{ $kpis['pending'] }}</div>
        <div class="label" style="margin-top:0.25rem;">awaiting action</div>
    </div>
    <div class="kpi">
        <div class="label">Average order value</div>
        <div class="value">{{ number_format($kpis['avg_order'], 2) }} {{ $kpis['currency'] }}</div>
    </div>
    <div class="kpi active">
        <div class="label">Sales</div>
        <div class="value">{{ number_format($kpis['sales'], 2) }} {{ $kpis['currency'] }}</div>
    </div>
</div>

<div class="card" style="margin-top:1rem;">
    <div class="card-head">
        <h3>Orders ({{ $kpis['count'] }})</h3>
    </div>

    <form method="get" action="{{ route('admin.orders.index') }}" style="margin-bottom:0.75rem;" data-auto-search="off">
        @php
            $filtersActive = collect(request()->only(['id', 'reference', 'customer', 'payment', 'status', 'date_from', 'date_to']))
                ->filter(fn ($v) => filled($v))
                ->isNotEmpty();
        @endphp
        <div style="display:grid;grid-template-columns:80px 1fr 1.2fr 1fr 1fr 1fr 1fr auto{{ $filtersActive ? ' auto' : '' }};gap:0.4rem;align-items:end;">
            <label>ID<input type="number" name="id" value="{{ request('id') }}"></label>
            <label>Reference<input name="reference" value="{{ request('reference') }}"></label>
            <label>Customer<input name="customer" value="{{ request('customer') }}"></label>
            <label>Payment<input name="payment" value="{{ request('payment') }}"></label>
            <label>Status
                <select name="status">
                    <option value="">—</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->code }}" @selected(request('status') === $status->code)>{{ $status->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>From<input type="date" name="date_from" value="{{ request('date_from') }}"></label>
            <label>To<input type="date" name="date_to" value="{{ request('date_to') }}"></label>
            <button class="btn btn-primary" type="submit">Search</button>
            @if($filtersActive)
                <a href="{{ route('admin.orders.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    <form method="post" action="{{ route('admin.orders.bulk') }}" id="orders-bulk-form">
        @csrf

        <div class="bulk-bar">
            <select name="action" id="bulk-action" required>
                <option value="">Bulk actions</option>
                <option value="delete">Delete selected</option>
            </select>
            <button class="btn btn-primary" type="submit" id="bulk-apply" disabled>Apply</button>
            <span class="team-muted" id="bulk-count">0 item(s) selected</span>
        </div>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                <tr>
                    <th style="width:2.25rem;">
                        <input type="checkbox" id="bulk-select-all" class="bulk-check" title="Select all" aria-label="Select all">
                    </th>
                    <th>ID</th>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Delivery</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                    @php
                        $invoice = $order->invoices->first();
                        $deliverySlip = $order->deliverySlips->first();
                    @endphp
                    <tr>
                        <td>
                            <input type="checkbox" name="ids[]" value="{{ $order->id }}" class="bulk-row-check bulk-check" aria-label="Select order {{ $order->id }}">
                        </td>
                        <td>{{ $order->id }}</td>
                        <td><strong>{{ $order->reference }}</strong></td>
                        <td>{{ $order->customer?->full_name ?? '—' }}</td>
                        <td>{{ $order->delivery_country ?: ($order->customer?->country ?: '—') }}</td>
                        <td>
                            <span style="background:#e8f5eb;color:#3d8b4f;padding:0.2rem 0.45rem;border-radius:3px;font-weight:600;">
                                {{ number_format((float) $order->total, 2) }} {{ $order->currency }}
                            </span>
                        </td>
                        <td>{{ $order->payment_method ?: '—' }}</td>
                        <td>
                            <span class="badge" style="background:{{ $statusMap->get($order->status)?->color ?? '#6c868e' }};color:#fff;">
                                {{ $statusMap->get($order->status)?->name ?? ucfirst($order->status) }}
                            </span>
                        </td>
                        <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                        <td style="text-align:right;white-space:nowrap;">
                            <div class="order-row-actions">
                                @if($invoice)
                                    <a class="order-action-btn"
                                       href="{{ route('admin.invoices.download', $invoice) }}"
                                       title="View invoice {{ $invoice->number }}"
                                       aria-label="View invoice">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14 2 14 8 20 8"/>
                                            <line x1="8" y1="13" x2="16" y2="13"/>
                                            <line x1="8" y1="17" x2="13" y2="17"/>
                                        </svg>
                                    </a>
                                @endif
                                @if($deliverySlip)
                                    <a class="order-action-btn"
                                       href="{{ route('admin.delivery-slips.download', $deliverySlip) }}"
                                       title="View delivery slip {{ $deliverySlip->number }}"
                                       aria-label="View delivery slip">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="1" y="3" width="15" height="13" rx="1"/>
                                            <path d="M16 8h4l3 3v5h-7V8z"/>
                                            <circle cx="5.5" cy="18.5" r="2.5"/>
                                            <circle cx="18.5" cy="18.5" r="2.5"/>
                                        </svg>
                                    </a>
                                @endif
                                <a class="order-action-btn"
                                   href="{{ route('admin.orders.show', $order) }}"
                                   title="View order"
                                   aria-label="View order">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="11" cy="11" r="7"/>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10">No orders found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <div style="margin-top:0.75rem;">{{ $orders->links() }}</div>
</div>
@endsection

@push('styles')
<style>
    .bulk-bar {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-bottom: 0.75rem;
    }
    .bulk-bar select {
        width: auto;
        min-width: 11rem;
    }
    .bulk-check {
        width: auto !important;
        min-width: 1rem;
        height: 1rem;
        margin: 0;
        padding: 0;
        accent-color: #25b9d7;
        cursor: pointer;
    }
    .order-row-actions {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.25rem;
    }
    .order-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border: 1px solid var(--ps-line);
        border-radius: 4px;
        background: #fff;
        color: #6c868e;
        text-decoration: none;
    }
    .order-action-btn:hover {
        color: #25b9d7;
        border-color: #25b9d7;
        background: #f4fbfd;
    }
    .order-action-btn svg {
        width: 1rem;
        height: 1rem;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const form = document.getElementById('orders-bulk-form');
    if (!form) return;

    const selectAll = document.getElementById('bulk-select-all');
    const rowChecks = () => Array.from(form.querySelectorAll('.bulk-row-check'));
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

    form.addEventListener('change', (e) => {
        if (e.target.classList.contains('bulk-row-check') || e.target === action) {
            syncBulkUi();
        }
    });

    form.addEventListener('submit', (e) => {
        const n = rowChecks().filter((el) => el.checked).length;
        if (n === 0) {
            e.preventDefault();
            alert('Please select at least one order.');
            return;
        }
        if (!action.value) {
            e.preventDefault();
            alert('Please choose a bulk action.');
            return;
        }
        if (action.value === 'delete' && !confirm('Delete ' + n + ' selected order(s)? This cannot be undone.')) {
            e.preventDefault();
        }
    });

    syncBulkUi();
})();
</script>
@endpush
