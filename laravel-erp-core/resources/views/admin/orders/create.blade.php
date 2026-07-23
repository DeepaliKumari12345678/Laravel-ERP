@extends('admin.layouts.app')

@section('title', 'New order')

@section('content')
<div class="ps-breadcrumb"><a href="{{ route('admin.orders.index') }}">Orders</a> &gt; New order</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">New order</h1>
    <a href="{{ route('admin.orders.index') }}" class="btn btn-ghost">Back to orders</a>
</div>

{{-- Customer step --}}
<div class="card" style="margin-bottom:1rem;">
    <h3 style="margin-top:0;">Customer</h3>

    @if($customer)
        <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:flex-start;">
            <div>
                <strong style="font-size:1.05rem;">{{ $customer->full_name }}</strong>
                <div style="color:var(--ps-muted);margin-top:0.25rem;">
                    {{ $customer->email ?: 'No email' }}
                    @if($customer->phone) · {{ $customer->phone }} @endif
                </div>
                @if($customer->address || $customer->city || $customer->country)
                    <div style="margin-top:0.55rem;padding:0.75rem;border:1px solid var(--ps-line);border-radius:4px;background:#fafbfc;max-width:420px;">
                        {{ $customer->address }}<br>
                        {{ collect([$customer->city, $customer->state, $customer->postcode])->filter()->implode(', ') }}
                        @if($customer->country)<br>{{ $customer->country }}@endif
                    </div>
                @endif
            </div>
            <form method="post" action="{{ route('admin.orders.cart.clear') }}">
                @csrf
                <button class="btn btn-ghost" type="submit" onclick="return confirm('Clear cart and change customer?')">Change customer</button>
            </form>
        </div>
    @else
        <form method="get" action="{{ route('admin.orders.create') }}" style="display:flex;gap:0.75rem;align-items:end;flex-wrap:wrap;">
            <label style="flex:1;min-width:240px;">Search for a customer
                <input type="search" name="customer_q" value="{{ $customer_q }}" placeholder="Type name, email or phone…" autofocus>
            </label>
            <button class="btn btn-primary" type="submit">Search</button>
            <span style="color:var(--ps-muted);padding-bottom:0.55rem;">or</span>
            <button class="btn btn-ghost" type="button" onclick="document.getElementById('new-customer').hidden=false">Add new customer</button>
        </form>
        <p style="color:var(--ps-muted);font-size:0.82rem;margin:0.45rem 0 0;">Search an existing customer by typing the first letters of their name.</p>

        @if($customer_q !== '')
            <div style="margin-top:1rem;">
                @forelse($customerResults as $result)
                    <form method="post" action="{{ route('admin.orders.customer.select') }}" style="display:flex;justify-content:space-between;gap:1rem;align-items:center;padding:0.65rem 0;border-bottom:1px solid var(--ps-line);">
                        @csrf
                        <input type="hidden" name="customer_id" value="{{ $result->id }}">
                        <div>
                            <strong>{{ $result->full_name }}</strong>
                            <div style="color:var(--ps-muted);font-size:0.85rem;">{{ $result->email }} {{ $result->phone ? '· '.$result->phone : '' }}</div>
                        </div>
                        <button class="btn btn-primary" type="submit">Choose</button>
                    </form>
                @empty
                    <p style="color:var(--ps-muted);">No customers found for “{{ $customer_q }}”.</p>
                @endforelse
            </div>
        @endif

        <div id="new-customer" style="margin-top:1.1rem;padding-top:1rem;border-top:1px solid var(--ps-line);" @if(!$errors->has('first_name')) hidden @endif>
            <h4 style="margin:0 0 0.75rem;">Add new customer</h4>
            <form method="post" action="{{ route('admin.orders.customer.store') }}">
                @csrf
                <div class="form-row">
                    <label>First name<input name="first_name" value="{{ old('first_name') }}" required></label>
                    <label>Last name<input name="last_name" value="{{ old('last_name') }}"></label>
                </div>
                <div class="form-row">
                    <label>Email<input type="email" name="email" value="{{ old('email') }}"></label>
                    <label>Phone<input name="phone" value="{{ old('phone') }}"></label>
                </div>
                <label>Address<textarea name="address" rows="2">{{ old('address') }}</textarea></label>
                <div class="form-row" style="margin-top:0.65rem;">
                    <label>City<input name="city" value="{{ old('city') }}"></label>
                    <label>Country
                        <select
                            id="order-customer-country"
                            name="country"
                            data-country-select
                            data-state-target="order-customer-state"
                            data-states-url="{{ route('admin.locations.states') }}"
                        >
                            @include('admin.partials.country-options', ['selectedCountry' => old('country')])
                        </select>
                    </label>
                </div>
                <div class="form-row">
                    <label>State / Province
                        <select id="order-customer-state" name="state" data-selected-state="{{ old('state') }}">
                            <option value="{{ old('state') }}">{{ old('state') ?: '— Select country first —' }}</option>
                        </select>
                    </label>
                </div>
                <button class="btn btn-primary" type="submit" style="margin-top:0.75rem;">Create & select customer</button>
            </form>
        </div>
    @endif
</div>

@if($customer)
{{-- Cart --}}
<div class="card" style="margin-bottom:1rem;">
    <div class="card-head">
        <h3>Cart</h3>
        <span style="color:var(--ps-muted);">Currency: {{ $currency }}</span>
    </div>

    <form method="get" action="{{ route('admin.orders.create') }}" style="display:flex;gap:0.75rem;align-items:end;flex-wrap:wrap;margin-bottom:0.85rem;">
        <label style="flex:1;min-width:240px;">Search for a product
            <input type="search" name="product_q" value="{{ $product_q }}" placeholder="Type product name or SKU…">
        </label>
        <button class="btn btn-primary" type="submit">Search</button>
    </form>

    @if($product_q !== '')
        <div style="margin-bottom:1rem;">
            @forelse($productResults as $product)
                <form method="post" action="{{ route('admin.orders.cart.add') }}" style="display:grid;grid-template-columns:1fr 100px auto;gap:0.55rem;align-items:center;padding:0.55rem 0;border-bottom:1px solid var(--ps-line);">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <div>
                        <strong>{{ $product->name }}</strong>
                        <div style="color:var(--ps-muted);font-size:0.82rem;">{{ $product->sku }} · {{ number_format((float)$product->price, 2) }} {{ $currency }} · stock {{ $product->quantity }}</div>
                    </div>
                    <input type="number" name="quantity" value="1" min="0.01" step="0.01">
                    <button class="btn btn-ok" type="submit">Add</button>
                </form>
            @empty
                <p style="color:var(--ps-muted);">No products found for “{{ $product_q }}”.</p>
            @endforelse
        </div>
    @endif

    <table>
        <thead>
        <tr><th>Product</th><th>Qty</th><th>Unit</th><th>Total</th><th></th></tr>
        </thead>
        <tbody>
        @forelse($lines as $line)
            <tr>
                <td>
                    <strong>{{ $line['product']->name }}</strong>
                    <div style="color:var(--ps-muted);font-size:0.8rem;">{{ $line['product']->sku }}</div>
                </td>
                <td>{{ $line['qty'] }}</td>
                <td>
                    {{ number_format($line['unit_price'], 2) }}
                    @if($line['discount_percent'] > 0)<div style="color:var(--success);font-size:.75rem;">{{ number_format($line['discount_percent'], 2) }}% group discount</div>@endif
                </td>
                <td><strong>{{ number_format($line['total'], 2) }}</strong></td>
                <td>
                    <form method="post" action="{{ route('admin.orders.cart.remove') }}">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $line['product']->id }}">
                        <button class="btn btn-danger" type="submit">Remove</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">Cart is empty. Search and add products above.</td></tr>
        @endforelse
        </tbody>
    </table>

    @if(count($lines))
        <div style="text-align:right;margin-top:0.85rem;font-size:1.1rem;">
            Products: <strong>{{ number_format($productsSubtotal, 2) }} {{ $currency }}</strong>
            @if($discountTotal > 0)<br><span style="color:var(--success);">Group discount: -{{ number_format($discountTotal, 2) }} {{ $currency }}</span>@endif
            <br>Subtotal: <strong>{{ number_format($subtotal, 2) }} {{ $currency }}</strong>
        </div>
    @endif
</div>

@if(count($lines))
<div class="card">
    <h3 style="margin-top:0;">Finish order</h3>
    @error('order')<div style="padding:.7rem;margin-bottom:.75rem;background:#fde8e6;color:var(--danger);border-radius:4px;">{{ $message }}</div>@enderror
    <form method="post" action="{{ route('admin.orders.store') }}">
        @csrf
        <div class="form-row">
            <label>Status
                <select name="status">
                    @foreach($orderStatuses as $status)
                        <option value="{{ $status->code }}" @selected($status->code === configuration('PS_ORDER_DEFAULT_STATUS', 'pending'))>{{ $status->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Payment method
                <input name="payment_method" value="{{ old('payment_method', 'Cash') }}" placeholder="Cash / Bank transfer / Card">
            </label>
        </div>
        <label style="margin-top:.7rem;">Shipping carrier
            <select id="shipping-carrier" name="shipping_carrier_id" @required($shippingQuotes->isNotEmpty())>
                @if($shippingQuotes->isEmpty())
                    <option value="">No carrier is available — shipping will be 0</option>
                @else
                    <option value="">— Select a carrier —</option>
                    @foreach($shippingQuotes as $quote)
                        <option
                            value="{{ $quote['carrier']->id }}"
                            data-shipping-total="{{ $quote['total'] }}"
                            @selected((string) old('shipping_carrier_id', $defaultCarrierId) === (string) $quote['carrier']->id)
                        >
                            {{ $quote['carrier']->name }} — {{ $quote['carrier']->delay }}
                            — {{ $quote['total'] > 0 ? number_format($quote['total'], 2).' '.$currency : 'Free' }}
                        </option>
                    @endforeach
                @endif
            </select>
            <span style="display:block;color:var(--ps-muted);font-size:.78rem;margin-top:.3rem;">
                Package weight: {{ number_format($shippingWeight, 3) }} {{ configuration('PS_PRODUCT_WEIGHT_UNIT', 'kg') }}.
                Availability is based on destination, rates, and carrier limits.
            </span>
            @error('shipping_carrier_id')<span style="display:block;color:var(--danger);margin-top:.3rem;">{{ $message }}</span>@enderror
        </label>
        <div style="margin:.8rem 0;padding:.75rem;background:#fafbfc;border:1px solid var(--ps-line);border-radius:4px;text-align:right;">
            Products: {{ number_format($productsSubtotal, 2) }} {{ $currency }}<br>
            @if($discountTotal > 0)Discount: -{{ number_format($discountTotal, 2) }} {{ $currency }}<br>@endif
            @if($productTax > 0)Tax: {{ number_format($productTax, 2) }} {{ $currency }}<br>@endif
            Shipping: <strong><span id="shipping-preview">—</span></strong><br>
            Order total: <strong><span id="order-total-preview">{{ number_format($subtotal + $productTax, 2) }}</span> {{ $currency }}</strong>
        </div>
        <label>Notes @if((string) configuration('PS_ORDER_NOTES_REQUIRED', '0') === '1')<span style="color:var(--danger);">*</span>@endif
            <textarea name="notes" rows="2" @required((string) configuration('PS_ORDER_NOTES_REQUIRED', '0') === '1')>{{ old('notes') }}</textarea>
        </label>
        <button class="btn btn-primary" type="submit" style="margin-top:0.75rem;">Create order</button>
    </form>
</div>
@endif
@endif

@push('scripts')
<script>
const shippingCarrier = document.getElementById('shipping-carrier');
const subtotal = {{ (float) ($subtotal + $productTax) }};
const currency = @json($currency);
function updateShippingPreview() {
    const option = shippingCarrier?.options[shippingCarrier.selectedIndex];
    const shipping = Number(option?.dataset.shippingTotal || 0);
    const shippingPreview = document.getElementById('shipping-preview');
    const totalPreview = document.getElementById('order-total-preview');
    if (shippingPreview) shippingPreview.textContent = option?.value ? `${shipping.toFixed(2)} ${currency}` : '—';
    if (totalPreview) totalPreview.textContent = (subtotal + shipping).toFixed(2);
}
shippingCarrier?.addEventListener('change', updateShippingPreview);
updateShippingPreview();
</script>
@endpush
@endsection
