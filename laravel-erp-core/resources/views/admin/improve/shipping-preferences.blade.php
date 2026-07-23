@extends('admin.layouts.app')

@section('title', 'Shipping preferences')

@section('content')
<style>
    .shipping-setting { display:grid;grid-template-columns:280px minmax(0,1fr);gap:1rem;align-items:start;padding:.8rem 0;border-bottom:1px solid #edf0f2; }
    .shipping-setting > span { text-align:right;font-weight:700;font-size:.8rem;padding-top:.55rem; }
    .shipping-setting small { display:block;color:var(--ps-muted);margin-top:.3rem; }
    @media(max-width:760px) { .shipping-setting { grid-template-columns:1fr;gap:.25rem; } .shipping-setting > span { text-align:left; } }
</style>

<div class="ps-breadcrumb">Improve &gt; Shipping &gt; Preferences</div>
<h1 class="page-title">Shipping preferences</h1>
<p class="page-sub">Global shipping charges and the default carrier used for new orders.</p>

<form method="post" action="{{ route('admin.shipping.preferences.update') }}">
    @csrf @method('PUT')

    <div class="card" style="margin-bottom:1rem;">
        <div class="card-head"><h3 style="margin:0;">Handling</h3></div>
        <label class="shipping-setting">
            <span>Handling charge</span>
            <span>
                <input type="number" step="0.01" min="0" name="PS_SHIPPING_HANDLING" value="{{ old('PS_SHIPPING_HANDLING', $values['PS_SHIPPING_HANDLING']) }}" required>
                <small>{{ $currency }} — added only when the carrier has “Add global handling charge” enabled.</small>
            </span>
        </label>
        <label class="shipping-setting">
            <span>Free shipping starts at</span>
            <span>
                <input type="number" step="0.01" min="0" name="PS_SHIPPING_FREE_PRICE" value="{{ old('PS_SHIPPING_FREE_PRICE', $values['PS_SHIPPING_FREE_PRICE']) }}" required>
                <small>{{ $currency }} — use 0 to disable this threshold.</small>
            </span>
        </label>
        <label class="shipping-setting">
            <span>Free shipping starts at weight</span>
            <span>
                <input type="number" step="0.001" min="0" name="PS_SHIPPING_FREE_WEIGHT" value="{{ old('PS_SHIPPING_FREE_WEIGHT', $values['PS_SHIPPING_FREE_WEIGHT']) }}" required>
                <small>{{ configuration('PS_PRODUCT_WEIGHT_UNIT', 'kg') }} — use 0 to disable this threshold.</small>
            </span>
        </label>
    </div>

    <div class="card">
        <div class="card-head"><h3 style="margin:0;">Carrier options</h3></div>
        <label class="shipping-setting">
            <span>Default carrier</span>
            <span>
                <select name="PS_SHIPPING_DEFAULT_CARRIER">
                    <option value="">First available carrier</option>
                    @foreach($carriers as $carrier)
                        <option value="{{ $carrier->id }}" @selected((string) old('PS_SHIPPING_DEFAULT_CARRIER', $values['PS_SHIPPING_DEFAULT_CARRIER']) === (string) $carrier->id)>{{ $carrier->name }}</option>
                    @endforeach
                </select>
                <small>Preselected while creating a new order.</small>
            </span>
        </label>
        <label class="shipping-setting">
            <span>Sort available carriers by</span>
            <span>
                <select name="PS_SHIPPING_SORT_BY" required>
                    <option value="position" @selected(old('PS_SHIPPING_SORT_BY', $values['PS_SHIPPING_SORT_BY']) === 'position')>Position</option>
                    <option value="price" @selected(old('PS_SHIPPING_SORT_BY', $values['PS_SHIPPING_SORT_BY']) === 'price')>Price</option>
                    <option value="name" @selected(old('PS_SHIPPING_SORT_BY', $values['PS_SHIPPING_SORT_BY']) === 'name')>Name</option>
                </select>
            </span>
        </label>
        <div style="display:flex;justify-content:flex-end;padding-top:1rem;">
            <button class="btn btn-primary" type="submit">Save preferences</button>
        </div>
    </div>
</form>
@endsection
