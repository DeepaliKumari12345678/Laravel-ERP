@extends('admin.layouts.app')

@section('title', 'Delivery slips')

@section('content')
<div class="ps-breadcrumb">
    <a href="{{ route('admin.orders.index') }}">Orders</a> &gt; Delivery slips
</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">Delivery slips</h1>
</div>

{{-- List --}}
<div class="card" style="margin-bottom:1rem;">
    <div class="card-head">
        <h3 style="margin:0;">Delivery slips ({{ $slips->total() }})</h3>
    </div>

    @if($slips->total() === 0)
        <div style="text-align:center;padding:2rem 1.5rem;">
            <h2 style="margin:0 0 0.5rem;font-size:1.25rem;">Print PDF delivery slips</h2>
            <p class="team-muted" style="max-width:36rem;margin:0 auto 1rem;line-height:1.5;">
                Generate delivery slips for shipped orders, then print or download them by date.
            </p>
        </div>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Carrier</th>
                    <th>Tracking</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($slips as $slip)
                    <tr>
                        <td>{{ $slip->number }}</td>
                        <td>{{ $slip->order?->reference ?? '—' }}</td>
                        <td>{{ $slip->customer?->full_name ?? '—' }}</td>
                        <td>{{ $slip->carrier ?: '—' }}</td>
                        <td>
                            @php($trackingUrl = $slip->carrier === $slip->order?->shipping_carrier_name
                                ? $slip->order?->shippingCarrier?->trackingUrlFor($slip->tracking_number)
                                : null)
                            @if($trackingUrl)
                                <a href="{{ $trackingUrl }}" target="_blank" rel="noopener">{{ $slip->tracking_number }}</a>
                            @else
                                {{ $slip->tracking_number ?: '—' }}
                            @endif
                        </td>
                        <td>{{ ucfirst($slip->status) }}</td>
                        <td style="text-align:right;white-space:nowrap;">
                            <a class="btn btn-ghost" href="{{ route('admin.delivery-slips.show', $slip) }}">View</a>
                            <a class="btn btn-primary" href="{{ route('admin.delivery-slips.download', $slip) }}">Download</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $slips->links() }}
    @endif
</div>

{{-- Print PDF by date --}}
<div class="card" style="margin-bottom:1rem;">
    <div class="card-head"><h3 style="margin:0;">Print PDF</h3></div>
    <form method="post" action="{{ route('admin.delivery-slips.pdf-by-date') }}">
        @csrf
        <div class="team-form-row">
            <div class="team-form-label">From</div>
            <div>
                <input type="date" name="from" value="{{ old('from', now()->toDateString()) }}" required>
                <div class="team-muted">Format: YYYY-MM-DD (inclusive).</div>
            </div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">To</div>
            <div>
                <input type="date" name="to" value="{{ old('to', now()->toDateString()) }}" required>
                <div class="team-muted">Format: YYYY-MM-DD (inclusive).</div>
            </div>
        </div>
        <div class="team-form-actions">
            <button class="btn btn-primary" type="submit">Generate PDF</button>
        </div>
    </form>
</div>

{{-- Options --}}
<div class="card" style="margin-bottom:1rem;">
    <div class="card-head"><h3 style="margin:0;">Delivery slip options</h3></div>
    <form method="post" action="{{ route('admin.delivery-slips.options.update') }}">
        @csrf
        @method('PUT')
        <div class="team-form-row">
            <div class="team-form-label">Delivery prefix</div>
            <div>
                <input name="prefix" value="{{ old('prefix', $options['prefix']) }}" required>
                <div class="team-muted">Prefix used for delivery slips (e.g. #DF).</div>
            </div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">Delivery number</div>
            <div>
                <input type="number" min="0" name="next_number" value="{{ old('next_number', $options['next_number']) }}">
                <div class="team-muted">The next delivery slip will start with this number. Set 0 to continue from the current sequence.</div>
            </div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">Enable product image</div>
            <div>
                <label>
                    <input type="hidden" name="product_image" value="0">
                    <input type="checkbox" name="product_image" value="1" style="width:auto;" @checked(old('product_image', $options['product_image']))>
                    Yes
                </label>
                <div class="team-muted">Adds product images on the delivery slip.</div>
            </div>
        </div>
        <div class="team-form-actions">
            <button class="btn btn-primary" type="submit">Save</button>
        </div>
    </form>
</div>

{{-- Create --}}
<div class="card">
    <div class="card-head"><h3 style="margin:0;">Create delivery slip</h3></div>
    <form method="post" action="{{ route('admin.delivery-slips.store') }}">
        @csrf
        <div class="team-form-row">
            <div class="team-form-label">Order *</div>
            <div>
                <select name="order_id" required>
                    <option value="">— Select order —</option>
                    @foreach($orders as $order)
                        <option value="{{ $order->id }}"
                            data-carrier="{{ $order->shipping_carrier_name }}"
                            @selected((string) old('order_id') === (string) $order->id)>
                            {{ $order->reference }}{{ $order->shipping_carrier_name ? ' · '.$order->shipping_carrier_name : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">Carrier</div>
            <div>
                <input id="delivery-carrier" name="carrier" list="carrier-options" value="{{ old('carrier') }}" placeholder="Choose or enter carrier">
                <datalist id="carrier-options">
                    @foreach($carriers as $carrier)
                        <option value="{{ $carrier->name }}">
                    @endforeach
                </datalist>
            </div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">Tracking number</div>
            <div><input name="tracking_number" value="{{ old('tracking_number') }}"></div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">Status</div>
            <div>
                <select name="status">
                    <option value="prepared" @selected(old('status', 'prepared') === 'prepared')>Prepared</option>
                    <option value="shipped" @selected(old('status') === 'shipped')>Shipped</option>
                    <option value="delivered" @selected(old('status') === 'delivered')>Delivered</option>
                </select>
            </div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">Notes</div>
            <div><textarea name="notes" rows="2">{{ old('notes') }}</textarea></div>
        </div>
        <div class="team-form-actions">
            <button class="btn btn-primary" type="submit">Create delivery slip</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const deliveryOrder = document.querySelector('select[name="order_id"]');
deliveryOrder?.addEventListener('change', () => {
    const carrier = deliveryOrder.options[deliveryOrder.selectedIndex]?.dataset.carrier || '';
    if (carrier) document.getElementById('delivery-carrier').value = carrier;
});
</script>
@endpush
