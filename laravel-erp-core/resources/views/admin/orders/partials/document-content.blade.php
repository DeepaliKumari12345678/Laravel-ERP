<style>
    .erp-document { color:#263238; font-family:DejaVu Sans, sans-serif; font-size:12px; }
    .erp-document * { box-sizing:border-box; }
    .erp-doc-header { display:table; width:100%; border-bottom:3px solid #25b9d7; padding-bottom:18px; margin-bottom:24px; }
    .erp-doc-header > div { display:table-cell; width:50%; vertical-align:top; }
    .erp-doc-header .doc-title { text-align:right; }
    .erp-doc-header h1 { margin:0 0 6px; color:#25b9d7; font-size:28px; text-transform:uppercase; }
    .erp-doc-header h2 { margin:0 0 7px; font-size:19px; }
    .erp-doc-muted { color:#6c7a80; }
    .erp-doc-addresses { display:table; width:100%; margin-bottom:24px; }
    .erp-doc-addresses > div { display:table-cell; width:50%; vertical-align:top; padding-right:25px; }
    .erp-doc-label { color:#6c7a80; font-size:10px; font-weight:bold; letter-spacing:.06em; margin-bottom:7px; text-transform:uppercase; }
    .erp-doc-box { background:#f5f7f8; border:1px solid #dfe5e8; min-height:95px; padding:12px; }
    .erp-doc-box strong { display:block; font-size:14px; margin-bottom:4px; }
    .erp-doc-meta { border-collapse:collapse; margin:0 0 22px auto; width:48%; }
    .erp-doc-meta td { border-bottom:1px solid #dfe5e8; padding:6px; }
    .erp-doc-meta td:first-child { color:#6c7a80; font-weight:bold; width:45%; }
    .erp-doc-table { border-collapse:collapse; margin-bottom:18px; width:100%; }
    .erp-doc-table th { background:#25b9d7; color:#fff; font-size:10px; padding:8px; text-align:left; text-transform:uppercase; }
    .erp-doc-table td { border-bottom:1px solid #dfe5e8; padding:8px; }
    .erp-doc-table .number { text-align:right; white-space:nowrap; }
    .erp-doc-summary { border-collapse:collapse; margin:0 0 22px auto; width:42%; }
    .erp-doc-summary td { border-bottom:1px solid #dfe5e8; padding:7px; }
    .erp-doc-summary .number { text-align:right; white-space:nowrap; }
    .erp-doc-summary .total td { border-top:2px solid #25b9d7; color:#25b9d7; font-size:14px; font-weight:bold; }
    .erp-doc-notes { background:#fffbea; border:1px solid #eadf9d; margin-top:20px; padding:11px; }
</style>

<div class="erp-document">
    <div class="erp-doc-header">
        <div>
            <h2>{{ $shop['name'] }}</h2>
            @if($shop['address'])<div>{{ $shop['address'] }}</div>@endif
            <div>{{ collect([$shop['city'], $shop['state'], $shop['postcode']])->filter()->implode(', ') }}</div>
            @if($shop['country'])<div>{{ $shop['country'] }}</div>@endif
            @if($shop['email'])<div>{{ $shop['email'] }}</div>@endif
            @if($shop['phone'])<div>{{ $shop['phone'] }}</div>@endif
        </div>
        <div class="doc-title">
            <h1>{{ $definition['title'] }}</h1>
            <strong>{{ $document->number }}</strong>
        </div>
    </div>

    <table class="erp-doc-meta">
        <tr>
            <td>Document date</td>
            <td>{{ $definition['date']?->format('d M Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td>Order</td>
            <td>{{ $order?->reference ?? '—' }}</td>
        </tr>
        <tr>
            <td>Status</td>
            <td>{{ ucfirst($document->status ?? 'issued') }}</td>
        </tr>
        @if($kind === 'delivery-slip')
            <tr>
                <td>Carrier</td>
                <td>{{ $document->carrier ?: '—' }}</td>
            </tr>
            <tr>
                <td>Tracking number</td>
                <td>{{ $document->tracking_number ?: '—' }}</td>
            </tr>
        @endif
    </table>

    <div class="erp-doc-addresses">
        <div>
            <div class="erp-doc-label">{{ $kind === 'delivery-slip' ? 'Deliver to' : 'Bill to' }}</div>
            <div class="erp-doc-box">
                <strong>{{ $customer?->company ?: ($customer?->full_name ?: 'Customer') }}</strong>
                @if($customer?->company && $customer?->full_name)<div>{{ $customer->full_name }}</div>@endif
                @if($customer?->address)<div>{{ $customer->address }}</div>@endif
                <div>{{ collect([$customer?->city, $customer?->state, $customer?->postcode])->filter()->implode(', ') }}</div>
                @if($customer?->country)<div>{{ $customer->country }}</div>@endif
                @if($customer?->email)<div>{{ $customer->email }}</div>@endif
                @if($customer?->phone)<div>{{ $customer->phone }}</div>@endif
            </div>
        </div>
        <div>
            <div class="erp-doc-label">Order information</div>
            <div class="erp-doc-box">
                <strong>{{ $order?->reference ?? '—' }}</strong>
                <div>Order date: {{ $order?->ordered_at?->format('d M Y') ?? '—' }}</div>
                @if($order?->payment_method)<div>Payment: {{ $order->payment_method }}</div>@endif
                @if($order?->delivery_country)<div>Delivery country: {{ $order->delivery_country }}</div>@endif
            </div>
        </div>
    </div>

    @php
        $showImg = ! empty($showProductImage);
        $colspan = ($kind === 'delivery-slip' ? 3 : 5) + ($showImg ? 1 : 0);
    @endphp
    <table class="erp-doc-table">
        <thead>
            <tr>
                @if($showImg)<th style="width:56px;"></th>@endif
                <th>Product</th>
                <th>SKU</th>
                <th class="number">Quantity</th>
                @if($kind !== 'delivery-slip')
                    <th class="number">Unit price</th>
                    <th class="number">Total</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($order?->items ?? [] as $item)
                @php
                    $rel = $item->product?->image_path;
                    $diskPath = $rel ? storage_path('app/public/'.$rel) : null;
                    $imageSrc = null;
                    if ($diskPath && is_file($diskPath)) {
                        $imageSrc = ! empty($forPdf)
                            ? $diskPath
                            : asset('storage/'.$rel);
                    }
                @endphp
                <tr>
                    @if($showImg)
                        <td>
                            @if($imageSrc)
                                <img src="{{ $imageSrc }}" alt="" style="width:42px;height:42px;object-fit:cover;">
                            @endif
                        </td>
                    @endif
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->sku ?: '—' }}</td>
                    <td class="number">{{ number_format((float) $item->quantity, 2) }}</td>
                    @if($kind !== 'delivery-slip')
                        <td class="number">{{ number_format((float) $item->unit_price, 2) }} {{ $definition['currency'] }}</td>
                        <td class="number">{{ number_format((float) $item->total, 2) }} {{ $definition['currency'] }}</td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $colspan }}">No order items available.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($kind === 'invoice')
        <table class="erp-doc-summary">
            <tr><td>Subtotal</td><td class="number">{{ number_format((float) $order?->subtotal, 2) }} {{ $definition['currency'] }}</td></tr>
            @if((float) $order?->shipping_cost > 0 || $order?->shipping_carrier_name)
                <tr><td>Shipping{{ $order?->shipping_carrier_name ? ' ('.$order->shipping_carrier_name.')' : '' }}</td><td class="number">{{ number_format((float) $order?->shipping_cost, 2) }} {{ $definition['currency'] }}</td></tr>
            @endif
            <tr><td>Tax</td><td class="number">{{ number_format((float) $order?->tax_total, 2) }} {{ $definition['currency'] }}</td></tr>
            @if((float) $order?->discount_total > 0)
                <tr><td>Discount</td><td class="number">-{{ number_format((float) $order->discount_total, 2) }} {{ $definition['currency'] }}</td></tr>
            @endif
            <tr class="total"><td>Total</td><td class="number">{{ number_format((float) $definition['amount'], 2) }} {{ $definition['currency'] }}</td></tr>
        </table>
    @elseif($kind === 'credit-slip')
        <table class="erp-doc-summary">
            <tr class="total"><td>Credit amount</td><td class="number">{{ number_format((float) $definition['amount'], 2) }} {{ $definition['currency'] }}</td></tr>
        </table>
    @endif

    @if($definition['notes'])
        <div class="erp-doc-notes">
            <strong>{{ $kind === 'credit-slip' ? 'Reason' : 'Notes' }}:</strong>
            {{ $definition['notes'] }}
        </div>
    @endif
</div>
