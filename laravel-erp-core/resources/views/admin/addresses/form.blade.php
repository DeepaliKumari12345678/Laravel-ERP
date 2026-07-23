@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Editing address '.$address->display_label : 'Add new address')

@section('content')
<style>
    .ad-form-card { max-width: 920px; margin: 0 auto; }
    .ad-form-row {
        display:grid; grid-template-columns: 210px 1fr; gap:1rem; align-items:start;
        padding:0.85rem 0; border-bottom:1px solid #f0f2f4;
    }
    .ad-form-row:last-of-type { border-bottom:0; }
    .ad-form-label { font-weight:600; color:var(--ps-ink); padding-top:0.55rem; }
    .ad-form-label .req { color:var(--danger); }
    .ad-hint { color:var(--ps-muted); font-size:0.78rem; margin-top:0.3rem; }
    .ad-customer-link {
        display:inline-flex; align-items:center; gap:0.4rem; padding-top:0.55rem;
        color:var(--ps-blue-dark); font-weight:600;
    }
    .ad-form-actions {
        display:flex; justify-content:space-between; gap:1rem; margin-top:1.25rem;
        padding-top:1rem; border-top:1px solid var(--ps-line);
    }
    .ad-check { display:flex; align-items:center; gap:0.45rem; padding-top:0.55rem; color:var(--ps-ink); }
    .ad-check input { width:auto; }
    @media (max-width:720px) {
        .ad-form-row { grid-template-columns:1fr; gap:0.35rem; }
        .ad-form-label { padding-top:0; }
    }
</style>

<div class="ps-breadcrumb">
    <a href="{{ route('admin.customers.index') }}">Customers</a> &gt;
    <a href="{{ route('admin.addresses.index') }}">Addresses</a> &gt;
    {{ $mode === 'edit' ? 'Edit' : 'Add' }}
</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">
        @if($mode === 'edit')
            Editing address {{ $address->display_label }}
        @else
            Add new address
        @endif
    </h1>
</div>

<div class="card ad-form-card">
    <div class="card-head">
        <h3 style="margin:0;display:flex;align-items:center;gap:0.45rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
            Address
        </h3>
    </div>

    <form method="post" action="{{ $mode === 'edit' ? route('admin.addresses.update', $address) : route('admin.addresses.store') }}">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <div class="ad-form-row">
            <div class="ad-form-label">Customer <span class="req">*</span></div>
            <div>
                @if($mode === 'edit' && $address->customer)
                    <input type="hidden" name="customer_id" value="{{ $address->customer_id }}">
                    <a class="ad-customer-link" href="{{ route('admin.customers.show', $address->customer) }}">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                        {{ $address->customer->full_name }}
                        @if($address->customer->email) ({{ $address->customer->email }}) @endif
                    </a>
                @else
                    <select name="customer_id" required>
                        <option value="">— Select customer —</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((int) old('customer_id', $address->customer_id) === $customer->id)>
                                {{ $customer->full_name }} @if($customer->email)({{ $customer->email }})@endif
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>

        <div class="ad-form-row">
            <div class="ad-form-label">Identification number</div>
            <div>
                <input name="dni" value="{{ old('dni', $address->dni) }}">
                <div class="ad-hint">National ID / tax identification number.</div>
            </div>
        </div>

        <div class="ad-form-row">
            <div class="ad-form-label">Address alias <span class="req">*</span></div>
            <div>
                <input name="alias" value="{{ old('alias', $address->alias) }}" required>
                <div class="ad-hint">e.g. Home, Office</div>
            </div>
        </div>

        <div class="ad-form-row">
            <div class="ad-form-label">First name <span class="req">*</span></div>
            <div>
                <input name="first_name" value="{{ old('first_name', $address->first_name) }}" required>
            </div>
        </div>

        <div class="ad-form-row">
            <div class="ad-form-label">Last name <span class="req">*</span></div>
            <div>
                <input name="last_name" value="{{ old('last_name', $address->last_name) }}" required>
            </div>
        </div>

        <div class="ad-form-row">
            <div class="ad-form-label">Company</div>
            <div>
                <input name="company" value="{{ old('company', $address->company) }}">
            </div>
        </div>

        <div class="ad-form-row">
            <div class="ad-form-label">VAT number</div>
            <div>
                <input name="vat_number" value="{{ old('vat_number', $address->vat_number) }}">
            </div>
        </div>

        <div class="ad-form-row">
            <div class="ad-form-label">Address <span class="req">*</span></div>
            <div>
                <input name="address1" value="{{ old('address1', $address->address1) }}" required>
            </div>
        </div>

        <div class="ad-form-row">
            <div class="ad-form-label">Address (2)</div>
            <div>
                <input name="address2" value="{{ old('address2', $address->address2) }}">
            </div>
        </div>

        <div class="ad-form-row">
            <div class="ad-form-label">Zip/Postal code <span class="req">*</span></div>
            <div>
                <input name="postcode" value="{{ old('postcode', $address->postcode) }}" required>
            </div>
        </div>

        <div class="ad-form-row">
            <div class="ad-form-label">City <span class="req">*</span></div>
            <div>
                <input name="city" value="{{ old('city', $address->city) }}" required>
            </div>
        </div>

        <div class="ad-form-row">
            <div class="ad-form-label">Country <span class="req">*</span></div>
            <div>
                <select
                    id="address-country"
                    name="country"
                    data-country-select
                    data-state-target="address-state"
                    data-states-url="{{ route('admin.locations.states') }}"
                    required
                >
                    @include('admin.partials.country-options', ['selectedCountry' => old('country', $address->country)])
                </select>
            </div>
        </div>

        <div class="ad-form-row">
            <div class="ad-form-label">State / Province</div>
            <div>
                <select id="address-state" name="state" data-selected-state="{{ old('state', $address->state) }}">
                    <option value="{{ old('state', $address->state) }}">{{ old('state', $address->state) ?: '— Select country first —' }}</option>
                </select>
            </div>
        </div>

        <div class="ad-form-row">
            <div class="ad-form-label">Phone</div>
            <div>
                <input name="phone" value="{{ old('phone', $address->phone) }}">
            </div>
        </div>

        <div class="ad-form-row">
            <div class="ad-form-label">Mobile phone</div>
            <div>
                <input name="phone_mobile" value="{{ old('phone_mobile', $address->phone_mobile) }}">
            </div>
        </div>

        <div class="ad-form-row">
            <div class="ad-form-label">Other</div>
            <div>
                <textarea name="other" rows="3">{{ old('other', $address->other) }}</textarea>
            </div>
        </div>

        <div class="ad-form-row">
            <div class="ad-form-label">Default address</div>
            <div>
                <label class="ad-check">
                    <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address->is_default))>
                    Use as default for this customer
                </label>
            </div>
        </div>

        <div class="ad-form-actions">
            <a href="{{ route('admin.addresses.index') }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary" type="submit">Save</button>
        </div>
    </form>
</div>
@endsection
