@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Editing supplier '.$supplier->name : 'Add new supplier')

@section('content')
<style>
    .bs-tabs { display:flex; gap:0; border-bottom:1px solid var(--ps-line); margin-bottom:1.25rem; }
    .bs-tabs a {
        padding:0.7rem 1.1rem; text-decoration:none; color:var(--ps-muted); font-weight:600;
        border-bottom:2px solid transparent; margin-bottom:-1px;
    }
    .bs-tabs a.active { color:var(--ps-ink); border-bottom-color:#25b9d7; }
    .sf-wrap { max-width: 980px; margin: 0 auto; }
    .sf-row {
        display: grid; grid-template-columns: 200px minmax(0, 1fr); gap: 1.25rem; align-items: start;
        padding: 1.1rem 0; border-bottom: 1px solid #f0f2f4;
    }
    .sf-row:last-of-type { border-bottom: 0; }
    .sf-label {
        font-weight: 600; color: var(--ps-ink); padding-top: 0.55rem;
        text-align: right;
    }
    .sf-label .req { color: var(--danger); }
    .sf-hint { color: var(--ps-muted); font-size: 0.78rem; margin-top: 0.35rem; }
    .sf-count { color: var(--ps-muted); font-size: 0.76rem; margin-top: 0.35rem; }
    .sf-switch-row { display: flex; align-items: center; gap: 0.75rem; padding-top: 0.25rem; }
    .sf-switch {
        position: relative; width: 44px; height: 24px; border-radius: 12px; border: 0; cursor: pointer;
        background: #bbcdd2; transition: background .15s; flex-shrink: 0;
    }
    .sf-switch.on { background: #70b580; }
    .sf-switch::after {
        content: ''; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px;
        border-radius: 50%; background: #fff; transition: left .15s;
    }
    .sf-switch.on::after { left: 23px; }
    .sf-file {
        display:flex; align-items:center; gap:0; max-width: 420px;
        border:1px solid var(--ps-line); border-radius:3px; overflow:hidden; background:#fff;
    }
    .sf-file input[type="file"] { display:none; }
    .sf-file-name {
        flex:1; padding:0.55rem 0.75rem; color:var(--ps-muted); font-size:0.88rem;
        white-space:nowrap; overflow:hidden; text-overflow:ellipsis; border:0; background:#fff;
    }
    .sf-file-browse {
        border:0; border-left:1px solid var(--ps-line); background:#f4f6f7;
        padding:0.55rem 0.9rem; cursor:pointer; font:inherit; color:var(--ps-ink);
    }
    .sf-file-browse:hover { background:#e9ecef; }
    .sf-logo-box { margin-top: 0.85rem; }
    .sf-preview {
        width: 140px; height: 140px; object-fit: contain; border: 1px solid var(--ps-line);
        border-radius: 3px; background: #fff; display: block;
    }
    .sf-logo-meta { color: var(--ps-muted); font-size: 0.78rem; margin: 0.4rem 0 0.55rem; }
    .sf-delete-logo {
        display:inline-flex; align-items:center; gap:0.35rem;
        border:1px solid var(--ps-line); background:#fff; color:var(--ps-ink);
        padding:0.35rem 0.7rem; border-radius:3px; cursor:pointer; font:inherit; font-size:0.85rem;
    }
    .sf-delete-logo:hover { border-color:var(--danger); color:var(--danger); }
    .sf-actions {
        display: flex; justify-content: space-between; gap: 1rem; margin-top: 1.5rem;
        max-width: 980px; margin-left: auto; margin-right: auto;
    }
    @media (max-width: 720px) {
        .sf-row { grid-template-columns: 1fr; }
        .sf-label { text-align: left; padding-top: 0; }
    }
</style>

<div class="ps-breadcrumb">Brands &amp; Suppliers &gt; Suppliers</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">
        {{ $mode === 'edit' ? 'Editing supplier '.$supplier->name : 'Add new supplier' }}
    </h1>
</div>

<div class="bs-tabs">
    <a href="{{ route('admin.catalog.brands') }}">Brands</a>
    <a href="{{ route('admin.catalog.suppliers') }}" class="active">Suppliers</a>
</div>

<form method="post" enctype="multipart/form-data"
      action="{{ $mode === 'edit' ? route('admin.catalog.suppliers.update', $supplier) : route('admin.catalog.suppliers.store') }}">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif

    <div class="card sf-wrap">
        <div class="card-head"><h3 style="margin:0;">Supplier</h3></div>

        <div class="sf-row">
            <div class="sf-label">Name <span class="req">*</span></div>
            <div>
                <input name="name" value="{{ old('name', $supplier->name) }}" required maxlength="150">
                <div class="sf-hint">Invalid characters: &lt;;&gt;;=#{}</div>
            </div>
        </div>

        <div class="sf-row">
            <div class="sf-label">Description</div>
            <div>
                <textarea name="description" id="supplier-description" rows="6" maxlength="50000">{{ old('description', $supplier->description) }}</textarea>
                <div class="sf-count">Will appear in the list of suppliers. <span id="desc-count">0</span> of 50000 characters allowed</div>
            </div>
        </div>

        <div class="sf-row">
            <div class="sf-label">Phone</div>
            <div><input name="phone" value="{{ old('phone', $supplier->phone) }}"></div>
        </div>

        <div class="sf-row">
            <div class="sf-label">Mobile phone</div>
            <div><input name="mobile_phone" value="{{ old('mobile_phone', $supplier->mobile_phone) }}"></div>
        </div>

        <div class="sf-row">
            <div class="sf-label">Address <span class="req">*</span></div>
            <div><input name="address" value="{{ old('address', $supplier->address) }}" required></div>
        </div>

        <div class="sf-row">
            <div class="sf-label">Address (2)</div>
            <div><input name="address2" value="{{ old('address2', $supplier->address2) }}"></div>
        </div>

        <div class="sf-row">
            <div class="sf-label">Zip/Postal code</div>
            <div><input name="postcode" value="{{ old('postcode', $supplier->postcode) }}"></div>
        </div>

        <div class="sf-row">
            <div class="sf-label">City <span class="req">*</span></div>
            <div><input name="city" value="{{ old('city', $supplier->city) }}" required></div>
        </div>

        <div class="sf-row">
            <div class="sf-label">Country <span class="req">*</span></div>
            <div>
                <select
                    id="supplier-country"
                    name="country"
                    required
                    data-country-select
                    data-state-target="supplier-state"
                    data-states-url="{{ route('admin.locations.states') }}"
                >
                    @include('admin.partials.country-options', ['selectedCountry' => old('country', $supplier->country)])
                </select>
            </div>
        </div>

        <div class="sf-row">
            <div class="sf-label">State</div>
            <div>
                <select id="supplier-state" name="state" data-selected-state="{{ old('state', $supplier->state) }}">
                    <option value="{{ old('state', $supplier->state) }}">{{ old('state', $supplier->state) ?: '— Select country first —' }}</option>
                </select>
            </div>
        </div>

        <div class="sf-row">
            <div class="sf-label">DNI</div>
            <div><input name="dni" value="{{ old('dni', $supplier->dni) }}"></div>
        </div>

        <div class="sf-row">
            <div class="sf-label">Logo</div>
            <div>
                <div class="sf-file">
                    <span class="sf-file-name" id="logo-file-name">Choose file(s)</span>
                    <button type="button" class="sf-file-browse" id="logo-browse">Browse</button>
                    <input type="file" name="logo" id="logo-input" accept="image/*">
                </div>
                <div class="sf-hint">Upload a supplier logo from your computer.</div>

                @if($supplier->logo_url)
                    <div class="sf-logo-box" id="logo-current">
                        <img src="{{ $supplier->logo_url }}" alt="{{ $supplier->name }}" class="sf-preview">
                        @if($supplier->logo_size_label)
                            <div class="sf-logo-meta">File size {{ $supplier->logo_size_label }}.</div>
                        @endif
                        <button type="button" class="sf-delete-logo" id="logo-delete-btn">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            Delete
                        </button>
                        <input type="hidden" name="remove_logo" id="remove-logo" value="0">
                    </div>
                @endif
            </div>
        </div>

        <div class="sf-row">
            <div class="sf-label">Meta title</div>
            <div>
                <input name="meta_title" value="{{ old('meta_title', $supplier->meta_title) }}" maxlength="255">
                <div class="sf-hint">Invalid characters: &lt;;&gt;;=#{}</div>
            </div>
        </div>

        <div class="sf-row">
            <div class="sf-label">Meta description</div>
            <div>
                <textarea name="meta_description" rows="3" maxlength="1000">{{ old('meta_description', $supplier->meta_description) }}</textarea>
                <div class="sf-hint">Invalid characters: &lt;;&gt;;=#{}</div>
            </div>
        </div>

        <div class="sf-row">
            <div class="sf-label">Enabled</div>
            <div class="sf-switch-row">
                <input type="hidden" name="active" value="0">
                <button type="button" class="sf-switch {{ old('active', $supplier->active) ? 'on' : '' }}"
                        id="supplier-active-switch" aria-label="Toggle enabled"></button>
                <input type="checkbox" name="active" value="1" id="supplier-active"
                       style="display:none;" @checked(old('active', $supplier->active))>
                <span id="supplier-active-label">{{ old('active', $supplier->active) ? 'Yes' : 'No' }}</span>
            </div>
        </div>
    </div>

    <div class="sf-actions">
        <a href="{{ route('admin.catalog.suppliers') }}" class="btn btn-ghost">Cancel</a>
        <button class="btn btn-primary" type="submit">Save</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const sw = document.getElementById('supplier-active-switch');
    const input = document.getElementById('supplier-active');
    const label = document.getElementById('supplier-active-label');
    if (sw && input) {
        sw.addEventListener('click', () => {
            input.checked = !input.checked;
            sw.classList.toggle('on', input.checked);
            if (label) label.textContent = input.checked ? 'Yes' : 'No';
        });
    }

    const desc = document.getElementById('supplier-description');
    const count = document.getElementById('desc-count');
    if (desc && count) {
        const sync = () => { count.textContent = String(desc.value.length); };
        desc.addEventListener('input', sync);
        sync();
    }

    const fileInput = document.getElementById('logo-input');
    const browseBtn = document.getElementById('logo-browse');
    const fileName = document.getElementById('logo-file-name');
    browseBtn?.addEventListener('click', () => fileInput?.click());
    fileInput?.addEventListener('change', () => {
        const name = fileInput.files?.[0]?.name;
        if (fileName) fileName.textContent = name || 'Choose file(s)';
        if (name) {
            const remove = document.getElementById('remove-logo');
            if (remove) remove.value = '0';
        }
    });

    const deleteBtn = document.getElementById('logo-delete-btn');
    const removeInput = document.getElementById('remove-logo');
    const logoCurrent = document.getElementById('logo-current');
    deleteBtn?.addEventListener('click', () => {
        if (!confirm('Delete this logo?')) return;
        if (removeInput) removeInput.value = '1';
        if (logoCurrent) logoCurrent.style.display = 'none';
        if (fileInput) fileInput.value = '';
        if (fileName) fileName.textContent = 'Choose file(s)';
    });
})();
</script>
@endpush
