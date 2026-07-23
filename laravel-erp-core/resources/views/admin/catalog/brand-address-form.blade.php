@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Edit brand address' : 'Add new brand address')

@section('content')
<style>
    .ba-form-card { max-width: 920px; margin: 0 auto; }
    .ba-row {
        display: grid; grid-template-columns: 200px 1fr; gap: 1rem; align-items: start;
        padding: 0.9rem 0; border-bottom: 1px solid #f0f2f4;
    }
    .ba-label { font-weight: 600; color: var(--ps-ink); padding-top: 0.45rem; }
    .ba-label .req { color: var(--danger); }
    .ba-actions {
        display: flex; justify-content: space-between; gap: 1rem; margin-top: 1.25rem;
        padding-top: 1rem; border-top: 1px solid var(--ps-line);
    }
    @media (max-width: 720px) { .ba-row { grid-template-columns: 1fr; } }
</style>

<div class="ps-breadcrumb">
    <a href="{{ route('admin.catalog.brands') }}">Brands &amp; Suppliers</a> &gt; Addresses &gt;
    {{ $mode === 'edit' ? 'Edit' : 'Add' }}
</div>

<div style="margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">
        {{ $mode === 'edit' ? 'Edit brand address' : 'Add new brand address' }}
    </h1>
</div>

<div class="card ba-form-card">
    <div class="card-head"><h3 style="margin:0;">Address</h3></div>

    <form method="post"
          action="{{ $mode === 'edit' ? route('admin.catalog.brands.addresses.update', $address) : route('admin.catalog.brands.addresses.store') }}">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <div class="ba-row">
            <div class="ba-label">Brand <span class="req">*</span></div>
            <div>
                <select name="brand_id" required>
                    <option value="">—</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" @selected((string) old('brand_id', $address->brand_id) === (string) $brand->id)>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="ba-row">
            <div class="ba-label">First name</div>
            <div><input name="first_name" value="{{ old('first_name', $address->first_name) }}"></div>
        </div>

        <div class="ba-row">
            <div class="ba-label">Last name</div>
            <div><input name="last_name" value="{{ old('last_name', $address->last_name) }}"></div>
        </div>

        <div class="ba-row">
            <div class="ba-label">Address</div>
            <div><input name="address1" value="{{ old('address1', $address->address1) }}"></div>
        </div>

        <div class="ba-row">
            <div class="ba-label">Address (2)</div>
            <div><input name="address2" value="{{ old('address2', $address->address2) }}"></div>
        </div>

        <div class="ba-row">
            <div class="ba-label">Zip/Postal code</div>
            <div><input name="postcode" value="{{ old('postcode', $address->postcode) }}"></div>
        </div>

        <div class="ba-row">
            <div class="ba-label">City</div>
            <div><input name="city" value="{{ old('city', $address->city) }}"></div>
        </div>

        <div class="ba-row">
            <div class="ba-label">Country</div>
            <div>
                <select
                    id="brand-address-country"
                    name="country"
                    data-country-select
                    data-state-target="brand-address-state"
                    data-states-url="{{ route('admin.locations.states') }}"
                >
                    @include('admin.partials.country-options', ['selectedCountry' => old('country', $address->country)])
                </select>
            </div>
        </div>

        <div class="ba-row">
            <div class="ba-label">State</div>
            <div>
                <select id="brand-address-state" name="state" data-selected-state="{{ old('state', $address->state) }}">
                    <option value="{{ old('state', $address->state) }}">{{ old('state', $address->state) ?: '— Select country first —' }}</option>
                </select>
            </div>
        </div>

        <div class="ba-row">
            <div class="ba-label">Phone</div>
            <div><input name="phone" value="{{ old('phone', $address->phone) }}"></div>
        </div>

        <div class="ba-actions">
            <a href="{{ route('admin.catalog.brands') }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary" type="submit">Save</button>
        </div>
    </form>
</div>
@endsection
