@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Edit carrier' : 'New carrier')

@section('content')
<style>
    .ship-tabs { display:flex;gap:0;border-bottom:1px solid var(--ps-line);margin-bottom:1rem;overflow-x:auto; }
    .ship-tab { border:0;background:transparent;padding:.8rem 1rem;color:var(--ps-muted);font-weight:700;white-space:nowrap;cursor:pointer; }
    .ship-tab.active { color:var(--ps-blue);border-bottom:3px solid var(--ps-blue); }
    .ship-panel { display:none; }
    .ship-panel.active { display:block; }
    .ship-row { display:grid;grid-template-columns:230px minmax(0,1fr);gap:1.2rem;padding:.8rem 0;border-bottom:1px solid #edf0f2; }
    .ship-label { text-align:right;font-weight:700;font-size:.8rem;padding-top:.55rem;color:var(--ps-ink); }
    .ship-hint { color:var(--ps-muted);font-size:.75rem;margin-top:.3rem; }
    .country-grid { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.45rem;max-height:300px;overflow:auto;border:1px solid var(--ps-line);padding:.75rem;border-radius:4px; }
    .country-grid label { display:flex;align-items:center;gap:.4rem;font-weight:400;color:var(--ps-ink); }
    .rate-row { display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:.5rem;align-items:end;margin-bottom:.5rem; }
    @media(max-width:800px) {
        .ship-row { grid-template-columns:1fr;gap:.3rem; }
        .ship-label { text-align:left; }
        .country-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
</style>

@php
    $selectedCountries = old('country_codes', $carrier->country_codes ?? []);
    $rangeRows = old('ranges');
    if (!is_array($rangeRows)) {
        $rangeRows = $carrier->exists
            ? $carrier->rateRanges->map(fn ($range) => [
                'from_value' => $range->from_value,
                'to_value' => $range->to_value,
                'price' => $range->price,
            ])->all()
            : [['from_value' => 0, 'to_value' => '', 'price' => 0]];
    }
@endphp

<div class="ps-breadcrumb">
    <a href="{{ route('admin.shipping.carriers') }}">Shipping &gt; Carriers</a> &gt;
    {{ $mode === 'edit' ? 'Edit' : 'New carrier' }}
</div>
<h1 class="page-title">{{ $mode === 'edit' ? 'Edit carrier' : 'New carrier' }}</h1>

<form method="post" enctype="multipart/form-data" action="{{ $mode === 'edit' ? route('admin.shipping.carriers.update', $carrier) : route('admin.shipping.carriers.store') }}">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif

    <div class="card">
        <div class="ship-tabs" role="tablist">
            <button type="button" class="ship-tab active" data-tab="general">General settings</button>
            <button type="button" class="ship-tab" data-tab="costs">Shipping locations and costs</button>
            <button type="button" class="ship-tab" data-tab="limits">Size and weight</button>
        </div>

        <section class="ship-panel active" data-panel="general">
            <div class="ship-row">
                <div class="ship-label">Carrier name *</div>
                <div><input name="name" value="{{ old('name', $carrier->name) }}" required></div>
            </div>
            <div class="ship-row">
                <div class="ship-label">Transit time *</div>
                <div>
                    <input name="delay" value="{{ old('delay', $carrier->delay) }}" placeholder="e.g. Delivery in 2–4 business days" required>
                    <div class="ship-hint">Shown when choosing a delivery service.</div>
                </div>
            </div>
            <div class="ship-row">
                <div class="ship-label">Enabled</div>
                <div><input type="hidden" name="active" value="0"><label><input type="checkbox" name="active" value="1" style="width:auto;" @checked((bool) old('active', $carrier->active))> Allow this carrier for new orders</label></div>
            </div>
            <div class="ship-row">
                <div class="ship-label">Speed grade</div>
                <div>
                    <input type="number" min="0" max="9" name="speed_grade" value="{{ old('speed_grade', $carrier->speed_grade ?? 0) }}">
                    <div class="ship-hint">0 means not rated; 9 is the fastest.</div>
                </div>
            </div>
            <div class="ship-row">
                <div class="ship-label">Logo</div>
                <div>
                    @if($carrier->logo_url)<img src="{{ $carrier->logo_url }}" alt="{{ $carrier->name }}" style="max-width:140px;max-height:60px;display:block;margin-bottom:.55rem;">@endif
                    <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    @if($carrier->logo_path)<label style="display:block;margin-top:.45rem;"><input type="checkbox" name="remove_logo" value="1" style="width:auto;"> Remove current logo</label>@endif
                </div>
            </div>
            <div class="ship-row">
                <div class="ship-label">Tracking URL</div>
                <div>
                    <input type="url" name="tracking_url" value="{{ old('tracking_url', $carrier->tracking_url) }}" placeholder="https://carrier.example/track?id=@">
                    <div class="ship-hint">Use @ where the tracking number should appear.</div>
                </div>
            </div>
            <div class="ship-row">
                <div class="ship-label">Position</div>
                <div><input type="number" min="0" name="position" value="{{ old('position', $carrier->position ?? 0) }}"></div>
            </div>
            <div class="ship-row">
                <div class="ship-label">Internal notes</div>
                <div><textarea name="notes" rows="3">{{ old('notes', $carrier->notes) }}</textarea></div>
            </div>
        </section>

        <section class="ship-panel" data-panel="costs">
            <div class="ship-row">
                <div class="ship-label">Available countries</div>
                <div>
                    <label style="display:block;margin-bottom:.55rem;"><input id="all-countries" type="checkbox" style="width:auto;" @checked(empty($selectedCountries))> Deliver to all countries</label>
                    <div id="country-grid" class="country-grid">
                        @foreach($countries as $code => $name)
                            <label><input type="checkbox" name="country_codes[]" value="{{ $code }}" style="width:auto;" @checked(in_array($code, $selectedCountries, true))> {{ $name }}</label>
                        @endforeach
                    </div>
                    <div class="ship-hint">Leave all countries selected by using “Deliver to all countries”.</div>
                </div>
            </div>
            <div class="ship-row">
                <div class="ship-label">Free shipping</div>
                <div><input type="hidden" name="free_shipping" value="0"><label><input type="checkbox" name="free_shipping" value="1" style="width:auto;" @checked((bool) old('free_shipping', $carrier->free_shipping))> This carrier is always free</label></div>
            </div>
            <div class="ship-row">
                <div class="ship-label">Tax rate</div>
                <div><input type="number" step="0.01" min="0" max="100" name="tax_rate" value="{{ old('tax_rate', $carrier->tax_rate ?? 0) }}"><div class="ship-hint">Percentage applied to the shipping charge.</div></div>
            </div>
            <div class="ship-row">
                <div class="ship-label">Handling cost</div>
                <div><input type="hidden" name="apply_handling_cost" value="0"><label><input type="checkbox" name="apply_handling_cost" value="1" style="width:auto;" @checked((bool) old('apply_handling_cost', $carrier->apply_handling_cost ?? true))> Add the global handling charge</label></div>
            </div>
            <div class="ship-row">
                <div class="ship-label">Calculate shipping by *</div>
                <div>
                    <label><input type="radio" name="billing_basis" value="price" style="width:auto;" @checked(old('billing_basis', $carrier->billing_basis ?? 'price') === 'price')> Order total price</label>
                    <label style="margin-left:1rem;"><input type="radio" name="billing_basis" value="weight" style="width:auto;" @checked(old('billing_basis', $carrier->billing_basis) === 'weight')> Total package weight</label>
                </div>
            </div>
            <div class="ship-row">
                <div class="ship-label">Base price</div>
                <div><input type="number" step="0.01" min="0" name="price" value="{{ old('price', $carrier->price ?? 0) }}"><div class="ship-hint">Used when no rate ranges are configured. Currency: {{ $currency }}.</div></div>
            </div>
            <div class="ship-row">
                <div class="ship-label">Rate ranges</div>
                <div>
                    <div id="rate-rows">
                        @foreach($rangeRows as $index => $range)
                            <div class="rate-row">
                                <label>From<input type="number" step="0.01" min="0" name="ranges[{{ $index }}][from_value]" value="{{ $range['from_value'] ?? 0 }}"></label>
                                <label>To<input type="number" step="0.01" min="0" name="ranges[{{ $index }}][to_value]" value="{{ $range['to_value'] ?? '' }}"></label>
                                <label>Shipping price<input type="number" step="0.01" min="0" name="ranges[{{ $index }}][price]" value="{{ $range['price'] ?? 0 }}"></label>
                                <button class="btn btn-ghost remove-rate" type="button">×</button>
                            </div>
                        @endforeach
                    </div>
                    <button id="add-rate" class="btn btn-ghost" type="button">+ Add range</button>
                    @error('ranges')<div style="color:var(--danger);margin-top:.4rem;">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="ship-row">
                <div class="ship-label">Outside configured ranges *</div>
                <div>
                    <select name="out_of_range_behavior">
                        <option value="disable" @selected(old('out_of_range_behavior', $carrier->out_of_range_behavior ?? 'disable') === 'disable')>Disable this carrier</option>
                        <option value="highest" @selected(old('out_of_range_behavior', $carrier->out_of_range_behavior) === 'highest')>Apply the cost of the highest range</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="ship-panel" data-panel="limits">
            <p style="color:var(--ps-muted);">Use 0 for no limit. A carrier is hidden when the order exceeds one of these limits.</p>
            <div class="ship-row"><div class="ship-label">Maximum package width</div><div><input type="number" step="0.01" min="0" name="max_width" value="{{ old('max_width', $carrier->max_width ?? 0) }}"><div class="ship-hint">{{ $dimensionUnit }}</div></div></div>
            <div class="ship-row"><div class="ship-label">Maximum package height</div><div><input type="number" step="0.01" min="0" name="max_height" value="{{ old('max_height', $carrier->max_height ?? 0) }}"><div class="ship-hint">{{ $dimensionUnit }}</div></div></div>
            <div class="ship-row"><div class="ship-label">Maximum package depth</div><div><input type="number" step="0.01" min="0" name="max_depth" value="{{ old('max_depth', $carrier->max_depth ?? 0) }}"><div class="ship-hint">{{ $dimensionUnit }}</div></div></div>
            <div class="ship-row"><div class="ship-label">Maximum package weight</div><div><input type="number" step="0.001" min="0" name="max_weight" value="{{ old('max_weight', $carrier->max_weight ?? 0) }}"><div class="ship-hint">{{ $weightUnit }}</div></div></div>
        </section>

        <div style="display:flex;justify-content:space-between;gap:.75rem;padding-top:1rem;">
            <a class="btn btn-ghost" href="{{ route('admin.shipping.carriers') }}">Cancel</a>
            <button class="btn btn-primary" type="submit">Save carrier</button>
        </div>
    </div>
</form>

@push('scripts')
<script>
const tabs = [...document.querySelectorAll('.ship-tab')];
const panels = [...document.querySelectorAll('.ship-panel')];
tabs.forEach(tab => tab.addEventListener('click', () => {
    tabs.forEach(item => item.classList.toggle('active', item === tab));
    panels.forEach(panel => panel.classList.toggle('active', panel.dataset.panel === tab.dataset.tab));
}));

const allCountries = document.getElementById('all-countries');
const countryBoxes = [...document.querySelectorAll('#country-grid input[type="checkbox"]')];
const syncCountries = () => countryBoxes.forEach(box => { box.disabled = allCountries.checked; if (allCountries.checked) box.checked = false; });
allCountries?.addEventListener('change', syncCountries);
syncCountries();

const rateRows = document.getElementById('rate-rows');
document.getElementById('add-rate')?.addEventListener('click', () => {
    const index = rateRows.querySelectorAll('.rate-row').length;
    rateRows.insertAdjacentHTML('beforeend', `<div class="rate-row">
        <label>From<input type="number" step="0.01" min="0" name="ranges[${index}][from_value]" value="0"></label>
        <label>To<input type="number" step="0.01" min="0" name="ranges[${index}][to_value]"></label>
        <label>Shipping price<input type="number" step="0.01" min="0" name="ranges[${index}][price]" value="0"></label>
        <button class="btn btn-ghost remove-rate" type="button">×</button>
    </div>`);
});
rateRows?.addEventListener('click', event => {
    if (event.target.classList.contains('remove-rate')) event.target.closest('.rate-row')?.remove();
});
</script>
@endpush
@endsection
