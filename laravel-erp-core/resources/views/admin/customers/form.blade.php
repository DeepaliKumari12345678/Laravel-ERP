@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Editing customer '.$customer->full_name : 'Add new customer')

@section('content')
<style>
    .cu-form-card { max-width: 920px; margin: 0 auto; }
    .cu-form-row {
        display:grid; grid-template-columns: 200px 1fr; gap:1rem; align-items:start;
        padding:0.85rem 0; border-bottom:1px solid #f0f2f4;
    }
    .cu-form-row:last-of-type { border-bottom:0; }
    .cu-form-label { font-weight:600; color:var(--ps-ink); padding-top:0.55rem; }
    .cu-form-label .req { color:var(--danger); }
    .cu-hint { color:var(--ps-muted); font-size:0.78rem; margin-top:0.3rem; }
    .cu-radios { display:flex; gap:1.25rem; padding-top:0.55rem; }
    .cu-radios label { display:flex; align-items:center; gap:0.4rem; color:var(--ps-ink); font-size:0.9rem; }
    .cu-radios input { width:auto; }
    .cu-switch-row { display:flex; align-items:center; gap:0.75rem; padding-top:0.35rem; }
    .cu-switch {
        position:relative; width:44px; height:24px; border-radius:12px; border:0; cursor:pointer;
        background:#bbcdd2; transition:background .15s; flex-shrink:0;
    }
    .cu-switch.on { background:#70b580; }
    .cu-switch::after {
        content:''; position:absolute; top:3px; left:3px; width:18px; height:18px;
        border-radius:50%; background:#fff; transition:left .15s;
    }
    .cu-switch.on::after { left:23px; }
    .cu-form-actions {
        display:flex; justify-content:space-between; gap:1rem; margin-top:1.25rem;
        padding-top:1rem; border-top:1px solid var(--ps-line);
    }
    @media (max-width:720px) {
        .cu-form-row { grid-template-columns:1fr; gap:0.35rem; }
        .cu-form-label { padding-top:0; }
    }
</style>

<div class="ps-breadcrumb">
    <a href="{{ route('admin.customers.index') }}">Customers</a>
    &gt; {{ $mode === 'edit' ? 'Edit' : 'Add' }}
</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">
        @if($mode === 'edit')
            Editing customer {{ $customer->full_name }}
        @else
            Add new customer
        @endif
    </h1>
</div>

<div class="card cu-form-card">
    <div class="card-head">
        <h3 style="display:flex;align-items:center;gap:0.45rem;margin:0;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="10" r="3"/><path d="M6.5 18.2c1.4-2 3.3-3 5.5-3s4.1 1 5.5 3"/></svg>
            Customer
        </h3>
    </div>

    <form method="post" action="{{ $mode === 'edit' ? route('admin.customers.update', $customer) : route('admin.customers.store') }}">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <div class="cu-form-row">
            <div class="cu-form-label">Social title</div>
            <div class="cu-radios">
                @foreach($titles as $title)
                    <label><input type="radio" name="social_title" value="{{ $title->name }}" @checked(old('social_title', $customer->social_title) === $title->name)> {{ $title->name }}</label>
                @endforeach
            </div>
        </div>

        <div class="cu-form-row">
            <div class="cu-form-label">First name <span class="req">*</span></div>
            <div>
                <input name="first_name" value="{{ old('first_name', $customer->first_name) }}" required>
            </div>
        </div>

        <div class="cu-form-row">
            <div class="cu-form-label">Last name</div>
            <div>
                <input name="last_name" value="{{ old('last_name', $customer->last_name) }}">
            </div>
        </div>

        <div class="cu-form-row">
            <div class="cu-form-label">Email <span class="req">*</span></div>
            <div>
                <input type="email" name="email" value="{{ old('email', $customer->email) }}" required>
            </div>
        </div>

        <div class="cu-form-row">
            <div class="cu-form-label">Phone</div>
            <div>
                <input name="phone" value="{{ old('phone', $customer->phone) }}">
            </div>
        </div>

        <div class="cu-form-row">
            <div class="cu-form-label">Company</div>
            <div>
                <input name="company" value="{{ old('company', $customer->company) }}">
            </div>
        </div>

        <div class="cu-form-row">
            <div class="cu-form-label">Customer type <span class="req">*</span></div>
            <div>
                <select name="type" required>
                    <option value="individual" @selected(old('type', $customer->type) === 'individual')>Individual</option>
                    <option value="company" @selected(old('type', $customer->type) === 'company')>Company</option>
                </select>
            </div>
        </div>

        <div class="cu-form-row">
            <div class="cu-form-label">Customer group</div>
            <div>
                <select name="customer_group_id">
                    <option value="">No group</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" @selected((string) old('customer_group_id', $customer->customer_group_id) === (string) $group->id)>
                            {{ $group->name }}{{ (float) $group->discount_percent > 0 ? ' — '.number_format((float) $group->discount_percent, 2).'% discount' : '' }}
                        </option>
                    @endforeach
                </select>
                <div class="cu-hint">The group discount is applied automatically to new admin orders.</div>
            </div>
        </div>

        <div class="cu-form-row">
            <div class="cu-form-label">Birthday</div>
            <div>
                <input type="date" name="birthday" value="{{ old('birthday', $customer->birthday?->format('Y-m-d')) }}">
            </div>
        </div>

        <div class="cu-form-row">
            <div class="cu-form-label">Address</div>
            <div>
                <textarea name="address" rows="2">{{ old('address', $customer->address) }}</textarea>
            </div>
        </div>

        <div class="cu-form-row">
            <div class="cu-form-label">City / Postcode</div>
            <div class="form-row" style="margin:0;">
                <input name="city" placeholder="City" value="{{ old('city', $customer->city) }}">
                <input name="postcode" placeholder="Postcode" value="{{ old('postcode', $customer->postcode) }}">
            </div>
        </div>

        <div class="cu-form-row">
            <div class="cu-form-label">Country</div>
            <div>
                <select
                    id="customer-country"
                    name="country"
                    data-country-select
                    data-state-target="customer-state"
                    data-states-url="{{ route('admin.locations.states') }}"
                >
                    @include('admin.partials.country-options', ['selectedCountry' => old('country', $customer->country)])
                </select>
            </div>
        </div>

        <div class="cu-form-row">
            <div class="cu-form-label">State / Province</div>
            <div>
                <select id="customer-state" name="state" data-selected-state="{{ old('state', $customer->state) }}">
                    <option value="{{ old('state', $customer->state) }}">{{ old('state', $customer->state) ?: '— Select country first —' }}</option>
                </select>
            </div>
        </div>

        <div class="cu-form-row">
            <div class="cu-form-label">Enabled</div>
            <div>
                <div class="cu-switch-row">
                    <button type="button" class="cu-switch {{ old('active', $customer->active) ? 'on' : '' }}" data-switch="active" aria-label="Enabled"></button>
                    <span class="switch-text">{{ old('active', $customer->active) ? 'Yes' : 'No' }}</span>
                    <input type="hidden" name="active" value="{{ old('active', $customer->active) ? '1' : '0' }}">
                </div>
                <div class="cu-hint">Enable or disable this customer.</div>
            </div>
        </div>

        <div class="cu-form-row">
            <div class="cu-form-label">Newsletter</div>
            <div>
                <div class="cu-switch-row">
                    <button type="button" class="cu-switch {{ old('newsletter', $customer->newsletter) ? 'on' : '' }}" data-switch="newsletter" aria-label="Newsletter"></button>
                    <span class="switch-text">{{ old('newsletter', $customer->newsletter) ? 'Yes' : 'No' }}</span>
                    <input type="hidden" name="newsletter" value="{{ old('newsletter', $customer->newsletter) ? '1' : '0' }}">
                </div>
            </div>
        </div>

        <div class="cu-form-row">
            <div class="cu-form-label">Partner offers</div>
            <div>
                <div class="cu-switch-row">
                    <button type="button" class="cu-switch {{ old('partner_offers', $customer->partner_offers) ? 'on' : '' }}" data-switch="partner_offers" aria-label="Partner offers"></button>
                    <span class="switch-text">{{ old('partner_offers', $customer->partner_offers) ? 'Yes' : 'No' }}</span>
                    <input type="hidden" name="partner_offers" value="{{ old('partner_offers', $customer->partner_offers) ? '1' : '0' }}">
                </div>
                <div class="cu-hint">This customer will receive your ads via email.</div>
            </div>
        </div>

        <div class="cu-form-actions">
            <a href="{{ $mode === 'edit' ? route('admin.customers.show', $customer) : route('admin.customers.index') }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary" type="submit">Save</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.querySelectorAll('[data-switch]').forEach(btn => {
    btn.addEventListener('click', () => {
        const name = btn.dataset.switch;
        const input = btn.parentElement.querySelector(`input[name="${name}"]`);
        const text = btn.parentElement.querySelector('.switch-text');
        const on = input.value !== '1';
        input.value = on ? '1' : '0';
        btn.classList.toggle('on', on);
        text.textContent = on ? 'Yes' : 'No';
    });
});
</script>
@endpush
@endsection
