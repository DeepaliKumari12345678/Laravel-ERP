@extends('admin.layouts.app')

@section('title', 'Order #'.$order->id.' '.$order->reference)

@section('content')
@php
    $statusMap = $statuses->keyBy('code');
    $docCount = $order->invoices->count() + $order->creditSlips->count() + $order->deliverySlips->count();
    $balance = (float) $order->total - (float) $paidAmount;
@endphp

<style>
    .od-header { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap; margin-bottom:1rem; }
    .od-header h1 { margin:0; color:#25b9d7; font-size:1.55rem; font-weight:600; }
    .od-actions-scroll {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 1rem;
    }
    .od-actions {
        display: flex;
        flex-wrap: nowrap;   /* ek line */
        align-items: center;
        gap: 0.45rem;
        width: max-content;  /* content jitna wide, scroll parent pe */
        min-width: 100%;
    }
    .od-actions select {
        width: auto;
        min-width: 180px;
        flex-shrink: 0;
    }
    .od-actions .btn,
    .od-actions .od-status-pill,
    .od-actions form {
        flex-shrink: 0;
    }
    .od-status-pill {
        display:inline-flex; align-items:center; gap:0.35rem;
        border:0; border-radius:4px; padding:0.45rem 0.75rem; color:#fff; font-weight:600; font-size:0.85rem;
        font-family:inherit; cursor:default;
    }
    .od-layout { display:grid; grid-template-columns:minmax(260px, 1fr) minmax(0, 2fr); gap:1rem; align-items:start; }
    .od-meta .stat-line span:first-child { color:var(--ps-muted); }
    .od-customer-head { display:flex; justify-content:space-between; align-items:center; gap:0.75rem; margin-bottom:0.75rem; }
    .od-customer-head strong { font-size:1.05rem; }
    .od-addresses { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-top:0.85rem; }
    .od-addr {
        border:1px solid var(--ps-line); border-radius:4px; padding:0.7rem 0.8rem; background:#fafbfc; font-size:0.86rem; line-height:1.45;
    }
    .od-addr .label { color:var(--ps-muted); font-size:0.72rem; text-transform:uppercase; letter-spacing:0.04em; font-weight:600; margin-bottom:0.35rem; }
    .od-totals {
        margin-top:1rem; margin-left:auto; width:min(280px, 100%);
        border:1px solid var(--ps-line); border-radius:4px; overflow:hidden;
    }
    .od-totals .row { display:flex; justify-content:space-between; padding:0.55rem 0.85rem; border-bottom:1px solid var(--ps-line); }
    .od-totals .row:last-child { border-bottom:0; background:#363a41; color:#fff; font-weight:700; }
    .od-tabs { display:flex; gap:0; border-bottom:1px solid var(--ps-line); margin:-1rem -1.1rem 1rem; padding:0 0.5rem; }
    .od-tabs a {
        display:inline-flex; align-items:center; gap:0.45rem;
        padding:0.85rem 1rem; color:var(--ps-muted); font-weight:600; font-size:0.88rem;
        border-bottom:3px solid transparent; margin-bottom:-1px;
    }
    .od-tabs a:hover { color:var(--ps-ink); }
    .od-tabs a.active { color:var(--ps-ink); border-bottom-color:var(--ps-blue); }
    .od-tabs a svg { width:18px; height:18px; flex-shrink:0; opacity:0.85; }
    .od-tabs a.active svg { opacity:1; }
    .od-status-row { display:flex; justify-content:space-between; gap:1rem; align-items:center; flex-wrap:wrap; padding:0.75rem 0; border-bottom:1px solid #f0f2f4; }
    .od-note-box { margin-top:1rem; }
    .prod-thumb {
        width:40px; height:40px; border-radius:3px; background:#eef2f4; color:#8a9ba3;
        display:grid; place-items:center; font-size:0.7rem; font-weight:700; flex-shrink:0;
    }
    .prod-thumb img { width:100%; height:100%; object-fit:cover; border-radius:3px; }
    .prod-cell { display:flex; gap:0.65rem; align-items:center; }
    @media (max-width: 980px) {
        .od-layout { grid-template-columns:1fr; }
        .od-addresses { grid-template-columns:1fr; }
    }
    @media print {
        .ps-header, .ps-sidebar, .od-actions, .no-print { display:none !important; }
        .ps-body { display:block; }
        .ps-content { padding:0; }
    }
</style>

<div @class(['ps-breadcrumb'])><a href="{{ route('admin.orders.index') }}">Orders</a> &gt; #{{ $order->id }} {{ $order->reference }}</div>

<div @class(['od-header'])>
    <h1>Order #{{ $order->id }} {{ $order->reference }}</h1>
    <div @class(['actions', 'no-print'])>
        <a href="{{ route('admin.orders.index') }}" @class(['btn', 'btn-ghost'])>← Back</a>
    </div>
</div>

{{-- <form method="post" action="{{ route('admin.orders.status', $order) }}" @class(['od-actions', 'no-print']) style="margin-bottom:1rem;">
    @csrf
    @method('PUT')
    <span @class(['od-status-pill']) style="background:{{ $statusMap->get($order->status)?->color ?? '#6c868e' }};">
        {{ $statusMap->get($order->status)?->name ?? ucfirst($order->status) }}
    </span>
    <select name="status" style="width:auto; min-width:180px;">
        @foreach($statuses as $status)
            <option value="{{ $status->code }}" @selected($order->status === $status->code)>{{ $status->name }}</option>
        @endforeach
    </select>
    <button @class(['btn', 'btn-primary']) type="submit">Update status</button>
</form> --}}

{{-- <div @class(['od-actions', 'no-print']) style="margin:-0.5rem 0 1rem;">
    @if($order->invoices->isNotEmpty())
        <a href="{{ route('admin.invoices.show', $order->invoices->first()) }}" @class(['btn', 'btn-ghost'])>View invoice</a>
        <a href="{{ route('admin.invoices.download', $order->invoices->first()) }}" @class(['btn', 'btn-primary'])>Download invoice</a>
    @else
        <form method="post" action="{{ route('admin.orders.invoice', $order) }}" style="display:inline;">
            @csrf
            <button @class(['btn', 'btn-ghost']) type="submit">Create invoice</button>
        </form>
    @endif
    <button @class(['btn', 'btn-ghost']) type="button" onclick="window.print()">Print order</button>
</div> --}}

<div class="od-actions-scroll no-print">
    <div class="od-actions">
        <form method="post" action="{{ route('admin.orders.status', $order) }}" style="display:contents;">
            @csrf
            @method('PUT')
            <span class="od-status-pill" style="background:{{ $statusMap->get($order->status)?->color ?? '#6c868e' }};">
                {{ $statusMap->get($order->status)?->name ?? ucfirst($order->status) }}
            </span>
            <select name="status">
                @foreach($statuses as $status)
                    <option value="{{ $status->code }}" @selected($order->status === $status->code)>{{ $status->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit">Update status</button>
        </form>

        @if($order->invoices->isNotEmpty())
            <a href="{{ route('admin.invoices.show', $order->invoices->first()) }}" class="btn btn-ghost">View invoice</a>
            <a href="{{ route('admin.invoices.download', $order->invoices->first()) }}" class="btn btn-primary">Download invoice</a>
        @else
            <form method="post" action="{{ route('admin.orders.invoice', $order) }}" style="display:inline;">
                @csrf
                <button class="btn btn-ghost" type="submit">Create invoice</button>
            </form>
        @endif
        <button class="btn btn-ghost" type="button" onclick="window.print()">Print order</button>
    </div>
</div>

<div @class(['od-layout'])>
    {{-- LEFT --}}
    <div style="display:grid; gap:1rem;">
        <div @class(['card', 'od-meta'])>
            <h3>Basic information</h3>
            <div @class(['stat-line'])><span>ID</span><strong>{{ $order->id }}</strong></div>
            <div @class(['stat-line'])><span>Order reference</span><strong>{{ $order->reference }}</strong></div>
            <div @class(['stat-line'])><span>Total price</span><strong>{{ number_format((float)$order->total, 2) }} {{ $order->currency }}</strong></div>
            <div @class(['stat-line'])><span>Created on</span><strong>{{ $order->created_at?->format('m/d/Y H:i:s') }}</strong></div>
            <div @class(['stat-line'])><span>Payment</span><strong>{{ $order->payment_method ?: '—' }}</strong></div>
            <div @class(['stat-line'])><span>Delivery</span><strong>{{ $order->shipping_carrier_name ?: '—' }}{{ $order->delivery_country ? ' · '.$order->delivery_country : '' }}</strong></div>
            <div @class(['stat-line'])><span>Employee</span><strong>{{ $order->employee?->full_name ?? '—' }}</strong></div>
        </div>

        <div @class(['card'])>
            <div @class(['od-customer-head'])>
                <div>
                    <strong>{{ $order->customer?->full_name ?? 'Guest' }}</strong>
                    @if($order->customer)
                        <span @class(['badge', 'badge-core']) style="margin-left:0.35rem;">Customer</span>
                    @endif
                </div>
                @if($order->customer)
                    <a href="{{ route('admin.customers.show', $order->customer) }}" @class(['btn', 'btn-ghost']) style="padding:0.35rem 0.65rem;">View details</a>
                @endif
            </div>

            @if($order->customer)
                <div @class(['stat-line'])><span>ID</span><strong>{{ $order->customer->id }}</strong></div>
                <div @class(['stat-line'])><span>Email</span><strong>{{ $order->customer->email }}</strong></div>
                <div @class(['stat-line'])><span>Customer type</span><strong>{{ ucfirst($order->customer->type ?? 'registered') }}</strong></div>
                <div @class(['stat-line'])><span>Account registered</span><strong>{{ $order->customer->created_at?->format('m/d/Y') }}</strong></div>
                <div @class(['stat-line'])><span>Total spent since registration</span><strong>{{ number_format($customerSpent, 2) }} {{ $order->currency }}</strong></div>
                <div @class(['stat-line'])><span>Validated orders placed</span><strong>{{ $customerOrdersCount }}</strong></div>

                <div @class(['od-addresses'])>
                    <div @class(['od-addr'])>
                        <div @class(['label'])>Shipping address</div>
                        <strong>{{ $order->customer->full_name }}</strong><br>
                        @if($order->customer->company){{ $order->customer->company }}<br>@endif
                        {{ $order->customer->address ?: '—' }}<br>
                        {{ collect([$order->customer->postcode, $order->customer->city])->filter()->implode(' ') }}<br>
                        {{ $order->customer->country }}
                        @if($order->customer->phone)<br>{{ $order->customer->phone }}@endif
                    </div>
                    <div @class(['od-addr'])>
                        <div @class(['label'])>Invoice address</div>
                        <strong>{{ $order->customer->full_name }}</strong><br>
                        @if($order->customer->company){{ $order->customer->company }}<br>@endif
                        {{ $order->customer->address ?: '—' }}<br>
                        {{ collect([$order->customer->postcode, $order->customer->city])->filter()->implode(' ') }}<br>
                        {{ $order->customer->country }}
                        @if($order->customer->phone)<br>{{ $order->customer->phone }}@endif
                    </div>
                </div>
            @else
                <p style="color:var(--ps-muted);margin:0;">No customer linked to this order.</p>
            @endif
        </div>
    </div>

    {{-- RIGHT --}}
    <div style="display:grid; gap:1rem;">
        <div @class(['card'])>
            <div @class(['card-head'])>
                <h3>Products ({{ $order->items->count() }})</h3>
            </div>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Base price</th>
                            <th>Qty</th>
                            <th>Available</th>
                            <th>Total</th>
                            <th>Invoice</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($order->items as $item)
                        <tr>
                            <td>
                                <div @class(['prod-cell'])>
                                    <div @class(['prod-thumb'])>
                                        @if($item->product?->image_url)
                                            <img src="{{ $item->product->image_url }}" alt="{{ $item->name }}">
                                        @else
                                            {{ strtoupper(substr($item->name, 0, 2)) }}
                                        @endif
                                    </div>
                                    <div>
                                        <strong>{{ $item->name }}</strong>
                                        <div style="color:var(--ps-muted);font-size:0.78rem;">Ref: {{ $item->sku ?: '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ number_format((float)$item->unit_price, 2) }} {{ $order->currency }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->product?->quantity ?? '—' }}</td>
                            <td><strong>{{ number_format((float)$item->total, 2) }} {{ $order->currency }}</strong></td>
                            <td>
                                @if($invoice = $order->invoices->first())
                                    <a href="{{ route('admin.invoices.show', $invoice) }}">{{ $invoice->number }}</a>
                                    <a href="{{ route('admin.invoices.download', $invoice) }}" title="Download invoice" style="margin-left:.35rem;">↓</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="color:var(--ps-muted);">No products on this order.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div @class(['od-totals'])>
                <div @class(['row'])><span>Products</span><span>{{ number_format((float)$order->subtotal, 2) }} {{ $order->currency }}</span></div>
                @if((float)$order->shipping_cost > 0 || $order->shipping_carrier_name)
                    <div @class(['row'])><span>Shipping ({{ $order->shipping_carrier_name ?: 'carrier' }})</span><span>{{ number_format((float)$order->shipping_cost, 2) }} {{ $order->currency }}</span></div>
                @endif
                <div @class(['row'])><span>Taxes</span><span>{{ number_format((float)$order->tax_total, 2) }} {{ $order->currency }}</span></div>
                @if((float)$order->discount_total > 0)
                    <div @class(['row'])><span>Discount</span><span>-{{ number_format((float)$order->discount_total, 2) }} {{ $order->currency }}</span></div>
                @endif
                <div @class(['row'])><span>Total</span><span>{{ number_format((float)$order->total, 2) }} {{ $order->currency }}</span></div>
            </div>
        </div>

        <div @class(['card'])>
            <div @class(['od-tabs', 'no-print'])>
                <a href="{{ route('admin.orders.show', ['order' => $order, 'tab' => 'status']) }}" @class(['active' => $tab === 'status'])>
                    {{-- status history / refresh --}}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 12a9 9 0 1 0 3-6.7"/>
                        <polyline points="3 4 3 9 8 9"/>
                        <polyline points="12 7 12 12 16 14"/>
                    </svg>
                    Status ({{ $order->statusHistories->count() }})
                </a>
                <a href="{{ route('admin.orders.show', ['order' => $order, 'tab' => 'documents']) }}" @class(['active' => $tab === 'documents'])>
                    {{-- documents --}}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="8" y1="13" x2="16" y2="13"/>
                        <line x1="8" y1="17" x2="13" y2="17"/>
                    </svg>
                    Documents ({{ $docCount }})
                </a>
                <a href="{{ route('admin.orders.show', ['order' => $order, 'tab' => 'payment']) }}" @class(['active' => $tab === 'payment'])>
                    {{-- credit card (Payment) --}}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <line x1="2" y1="10" x2="22" y2="10"/>
                    </svg>
                    Payment ({{ $order->payments->count() }})
                </a>
            </div>

            @if($tab === 'documents')
                <div @class(['card-head'])>
                    <h3 style="margin:0;font-size:0.95rem;">Documents</h3>
                    <div @class(['no-print']) style="display:flex;gap:0.45rem;flex-wrap:wrap;">
                        @if($order->invoices->isEmpty() && ($invoicesEnabled ?? true))
                            <form method="post" action="{{ route('admin.orders.invoice', $order) }}">
                                @csrf
                                <button @class(['btn', 'btn-primary']) type="submit">Generate invoice</button>
                            </form>
                        @elseif($order->invoices->isEmpty() && ! ($invoicesEnabled ?? true))
                            <span @class(['team-muted']) style="font-size:0.85rem;">Invoices are disabled in Invoice options.</span>
                        @endif
                        <form method="post" action="{{ route('admin.orders.credit-slip', $order) }}">
                            @csrf
                            <button @class(['btn', 'btn-ghost']) type="submit">Generate credit slip</button>
                        </form>
                        <form method="post" action="{{ route('admin.orders.delivery-slip', $order) }}">
                            @csrf
                            <button @class(['btn', 'btn-ghost']) type="submit">Generate delivery slip</button>
                        </form>
                    </div>
                </div>
                <table>
                    <thead><tr><th>Type</th><th>Number</th><th>Date</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    @forelse($order->invoices as $inv)
                        <tr>
                            <td>Invoice</td>
                            <td>{{ $inv->number }}</td>
                            <td>{{ $inv->issued_at?->format('Y-m-d') ?? $inv->created_at?->format('Y-m-d') }}</td>
                            <td>{{ number_format((float)$inv->total, 2) }} {{ $inv->currency }}</td>
                            <td>{{ ucfirst($inv->status) }}</td>
                            <td style="white-space:nowrap;">
                                <a @class(['btn', 'btn-ghost']) href="{{ route('admin.invoices.show', $inv) }}">View</a>
                                <a @class(['btn', 'btn-primary']) href="{{ route('admin.invoices.download', $inv) }}">Download</a>
                            </td>
                        </tr>
                    @empty
                    @endforelse
                    @foreach($order->creditSlips as $slip)
                        <tr>
                            <td>Credit slip</td>
                            <td>{{ $slip->number }}</td>
                            <td>{{ $slip->issued_at?->format('Y-m-d') ?? $slip->created_at?->format('Y-m-d') }}</td>
                            <td>{{ number_format((float)$slip->amount, 2) }} {{ $slip->currency ?? $order->currency }}</td>
                            <td>{{ ucfirst($slip->status ?? 'issued') }}</td>
                            <td style="white-space:nowrap;">
                                <a @class(['btn', 'btn-ghost']) href="{{ route('admin.credit-slips.show', $slip) }}">View</a>
                                <a @class(['btn', 'btn-primary']) href="{{ route('admin.credit-slips.download', $slip) }}">Download</a>
                            </td>
                        </tr>
                    @endforeach
                    @foreach($order->deliverySlips as $slip)
                        <tr>
                            <td>Delivery slip</td>
                            <td>{{ $slip->number }}</td>
                            <td>{{ $slip->shipped_at?->format('Y-m-d') ?? $slip->created_at?->format('Y-m-d') }}</td>
                            <td>—</td>
                            <td>{{ ucfirst($slip->status ?? 'issued') }}</td>
                            <td style="white-space:nowrap;">
                                <a @class(['btn', 'btn-ghost']) href="{{ route('admin.delivery-slips.show', $slip) }}">View</a>
                                <a @class(['btn', 'btn-primary']) href="{{ route('admin.delivery-slips.download', $slip) }}">Download</a>
                            </td>
                        </tr>
                    @endforeach
                    @if($docCount === 0)
                        <tr><td colspan="6" style="color:var(--ps-muted);">There is no available document.</td></tr>
                    @endif
                    </tbody>
                </table>

            @elseif($tab === 'payment')
                <div @class(['card-head'])>
                    <h3 style="margin:0;font-size:0.95rem;">Payments</h3>
                    <span style="color:var(--ps-muted);font-size:0.85rem;">
                        Paid {{ number_format($paidAmount, 2) }} / {{ number_format((float)$order->total, 2) }} {{ $order->currency }}
                        @if($balance > 0.009)
                            · Balance <strong style="color:var(--danger);">{{ number_format($balance, 2) }}</strong>
                        @else
                            · <span @class(['badge', 'badge-on'])>Paid in full</span>
                        @endif
                    </span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Payment method</th>
                            <th>Transaction ID</th>
                            <th>Amount</th>
                            <th>Employee</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($order->payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at?->format('Y-m-d H:i') ?? $payment->created_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $payment->payment_method }}</td>
                            <td>{{ $payment->transaction_id ?: '—' }}</td>
                            <td>{{ number_format((float)$payment->amount, 2) }} {{ $payment->currency }}</td>
                            <td>{{ $payment->employee?->full_name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="color:var(--ps-muted);">No payments recorded.</td></tr>
                    @endforelse
                    </tbody>
                </table>

                <form method="post" action="{{ route('admin.orders.payments.store', $order) }}" @class(['no-print']) style="margin-top:1rem;">
                    @csrf
                    <div @class(['form-row']) style="grid-template-columns:1.2fr 1fr 1fr auto; align-items:end;">
                        <label>Payment method
                            <input type="text" name="payment_method" value="{{ old('payment_method', $order->payment_method) }}" required>
                        </label>
                        <label>Transaction ID
                            <input type="text" name="transaction_id" value="{{ old('transaction_id') }}">
                        </label>
                        <label>Amount
                            <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', max($balance, 0.01)) }}" required>
                        </label>
                        <button @class(['btn', 'btn-primary']) type="submit">Add</button>
                    </div>
                </form>

            @else
                @forelse($order->statusHistories as $history)
                    <div @class(['od-status-row'])>
                        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                            <span @class(['badge']) style="background:{{ $statusMap->get($history->status)?->color ?? '#6c868e' }};color:#fff;">
                                {{ $statusMap->get($history->status)?->name ?? ucfirst($history->status) }}
                            </span>
                            <span style="color:var(--ps-muted);font-size:0.85rem;">
                                {{ $history->created_at?->format('m/d/Y H:i:s') }}
                                · {{ $history->employee?->full_name ?? 'System' }}
                                @if($history->comment) · {{ $history->comment }}@endif
                            </span>
                        </div>
                    </div>
                @empty
                    <p style="color:var(--ps-muted);">No status history yet.</p>
                @endforelse

                <form method="post" action="{{ route('admin.orders.status', $order) }}" @class(['no-print']) style="margin-top:1rem;display:flex;gap:0.5rem;align-items:end;flex-wrap:wrap;">
                    @csrf
                    @method('PUT')
                    <label style="flex:1;min-width:180px;">Update status
                        <select name="status">
                            @foreach($statuses as $status)
                                <option value="{{ $status->code }}" @selected($order->status === $status->code)>{{ $status->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label style="flex:1.4;min-width:200px;">Comment
                        <input type="text" name="comment" placeholder="Optional note">
                    </label>
                    <button @class(['btn', 'btn-primary']) type="submit">Update status</button>
                </form>
            @endif
        </div>

        <div @class(['card', 'od-note-box'])>
            <div @class(['card-head'])>
                <h3 style="margin:0;">Order private note</h3>
            </div>
            <form method="post" action="{{ route('admin.orders.note', $order) }}" @class(['no-print'])>
                @csrf
                @method('PUT')
                <textarea name="notes" rows="3" placeholder="Add an internal note for this order…">{{ old('notes', $order->notes) }}</textarea>
                <div style="margin-top:0.65rem;">
                    <button @class(['btn', 'btn-primary']) type="submit">Save note</button>
                </div>
            </form>
            @if($order->notes)
                <div @class(['no-print']) style="display:none;"></div>
            @endif
        </div>
    </div>
</div>
@endsection
