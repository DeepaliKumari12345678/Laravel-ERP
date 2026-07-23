@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Editing brand '.$brand->name : 'Add new brand')

@section('content')
<style>
    .bs-tabs { display:flex; gap:0; border-bottom:1px solid var(--ps-line); margin-bottom:1.25rem; }
    .bs-tabs a {
        padding:0.7rem 1.1rem; text-decoration:none; color:var(--ps-muted); font-weight:600;
        border-bottom:2px solid transparent; margin-bottom:-1px;
    }
    .bs-tabs a.active { color:var(--ps-ink); border-bottom-color:#25b9d7; }
    .bf-wrap { max-width: 980px; margin: 0 auto; }
    .bf-form-card { background:#fff; }
    .bf-row {
        display: grid; grid-template-columns: 200px minmax(0, 1fr); gap: 1.25rem; align-items: start;
        padding: 1.1rem 0; border-bottom: 1px solid #f0f2f4;
    }
    .bf-row:last-of-type { border-bottom: 0; }
    .bf-label {
        font-weight: 600; color: var(--ps-ink); padding-top: 0.55rem;
        text-align: right;
    }
    .bf-label .req { color: var(--danger); }
    .bf-hint { color: var(--ps-muted); font-size: 0.78rem; margin-top: 0.35rem; }
    .bf-count { color: var(--ps-muted); font-size: 0.76rem; margin-top: 0.35rem; }
    .bf-switch-row { display: flex; align-items: center; gap: 0.75rem; padding-top: 0.25rem; }
    .bf-switch {
        position: relative; width: 44px; height: 24px; border-radius: 12px; border: 0; cursor: pointer;
        background: #bbcdd2; transition: background .15s; flex-shrink: 0;
    }
    .bf-switch.on { background: #70b580; }
    .bf-switch::after {
        content: ''; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px;
        border-radius: 50%; background: #fff; transition: left .15s;
    }
    .bf-switch.on::after { left: 23px; }
    .bf-file {
        display:flex; align-items:center; gap:0; max-width: 420px;
        border:1px solid var(--ps-line); border-radius:3px; overflow:hidden; background:#fff;
    }
    .bf-file input[type="file"] { display:none; }
    .bf-file-name {
        flex:1; padding:0.55rem 0.75rem; color:var(--ps-muted); font-size:0.88rem;
        white-space:nowrap; overflow:hidden; text-overflow:ellipsis; border:0; background:#fff;
    }
    .bf-file-browse {
        border:0; border-left:1px solid var(--ps-line); background:#f4f6f7;
        padding:0.55rem 0.9rem; cursor:pointer; font:inherit; color:var(--ps-ink);
    }
    .bf-file-browse:hover { background:#e9ecef; }
    .bf-logo-box { margin-top: 0.85rem; }
    .bf-preview {
        width: 140px; height: 140px; object-fit: contain; border: 1px solid var(--ps-line);
        border-radius: 3px; background: #fff; display: block;
    }
    .bf-logo-meta { color: var(--ps-muted); font-size: 0.78rem; margin: 0.4rem 0 0.55rem; }
    .bf-delete-logo {
        display:inline-flex; align-items:center; gap:0.35rem;
        border:1px solid var(--ps-line); background:#fff; color:var(--ps-ink);
        padding:0.35rem 0.7rem; border-radius:3px; cursor:pointer; font:inherit; font-size:0.85rem;
    }
    .bf-delete-logo:hover { border-color:var(--danger); color:var(--danger); }
    .bf-actions {
        display: flex; justify-content: space-between; gap: 1rem; margin-top: 1.5rem;
        max-width: 980px; margin-left: auto; margin-right: auto;
    }
    @media (max-width: 720px) {
        .bf-row { grid-template-columns: 1fr; }
        .bf-label { text-align: left; padding-top: 0; }
    }
</style>

<div class="ps-breadcrumb">Brands &amp; Suppliers &gt; Brands</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">
        {{ $mode === 'edit' ? 'Editing brand '.$brand->name : 'Add new brand' }}
    </h1>
</div>

<div class="bs-tabs">
    <a href="{{ route('admin.catalog.brands') }}" class="active">Brands</a>
    <a href="{{ route('admin.catalog.suppliers') }}">Suppliers</a>
</div>

<form method="post" enctype="multipart/form-data"
      action="{{ $mode === 'edit' ? route('admin.catalog.brands.update', $brand) : route('admin.catalog.brands.store') }}">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif

    <div class="card bf-wrap bf-form-card">
        <div class="card-head"><h3 style="margin:0;">Brand</h3></div>

        <div class="bf-row">
            <div class="bf-label">Name <span class="req">*</span></div>
            <div>
                <input name="name" value="{{ old('name', $brand->name) }}" required maxlength="150">
                <div class="bf-hint">Invalid characters: &lt;;&gt;;=#{}</div>
            </div>
        </div>

        <div class="bf-row">
            <div class="bf-label">Short description</div>
            <div>
                <textarea name="description" id="brand-short-desc" rows="5" maxlength="5000">{{ old('description', $brand->description) }}</textarea>
                <div class="bf-count"><span id="short-count">0</span> of 5000 characters allowed</div>
            </div>
        </div>

        <div class="bf-row">
            <div class="bf-label">Description</div>
            <div>
                <textarea name="long_description" id="brand-long-desc" rows="7" maxlength="50000">{{ old('long_description', $brand->long_description) }}</textarea>
                <div class="bf-count"><span id="long-count">0</span> of 50000 characters allowed</div>
            </div>
        </div>

        <div class="bf-row">
            <div class="bf-label">Logo</div>
            <div>
                <div class="bf-file">
                    <span class="bf-file-name" id="logo-file-name">Choose file(s)</span>
                    <button type="button" class="bf-file-browse" id="logo-browse">Browse</button>
                    <input type="file" name="logo" id="logo-input" accept="image/*">
                </div>

                @if($brand->logo_url)
                    <div class="bf-logo-box" id="logo-current">
                        <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" class="bf-preview">
                        @if($brand->logo_size_label)
                            <div class="bf-logo-meta">File size {{ $brand->logo_size_label }}.</div>
                        @endif
                        <button type="button" class="bf-delete-logo" id="logo-delete-btn">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            Delete
                        </button>
                        <input type="hidden" name="remove_logo" id="remove-logo" value="0">
                    </div>
                @endif
            </div>
        </div>

        <div class="bf-row">
            <div class="bf-label">Meta title</div>
            <div>
                <input name="meta_title" value="{{ old('meta_title', $brand->meta_title) }}" maxlength="255">
                <div class="bf-hint">Invalid characters: &lt;;&gt;;=#{}</div>
            </div>
        </div>

        <div class="bf-row">
            <div class="bf-label">Meta description</div>
            <div>
                <textarea name="meta_description" rows="3" maxlength="1000">{{ old('meta_description', $brand->meta_description) }}</textarea>
                <div class="bf-hint">Invalid characters: &lt;;&gt;;=#{}</div>
            </div>
        </div>

        <div class="bf-row">
            <div class="bf-label">Enabled</div>
            <div class="bf-switch-row">
                <input type="hidden" name="active" value="0">
                <button type="button" class="bf-switch {{ old('active', $brand->active) ? 'on' : '' }}"
                        id="brand-active-switch" aria-label="Toggle enabled"></button>
                <input type="checkbox" name="active" value="1" id="brand-active"
                       style="display:none;" @checked(old('active', $brand->active))>
                <span id="brand-active-label">{{ old('active', $brand->active) ? 'Yes' : 'No' }}</span>
            </div>
        </div>
    </div>

    <div class="bf-actions">
        <a href="{{ route('admin.catalog.brands') }}" class="btn btn-ghost">Cancel</a>
        <button class="btn btn-primary" type="submit">Save</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const sw = document.getElementById('brand-active-switch');
    const input = document.getElementById('brand-active');
    const label = document.getElementById('brand-active-label');
    if (sw && input) {
        sw.addEventListener('click', () => {
            input.checked = !input.checked;
            sw.classList.toggle('on', input.checked);
            if (label) label.textContent = input.checked ? 'Yes' : 'No';
        });
    }

    function bindCount(textareaId, countId) {
        const el = document.getElementById(textareaId);
        const out = document.getElementById(countId);
        if (!el || !out) return;
        const sync = () => { out.textContent = String(el.value.length); };
        el.addEventListener('input', sync);
        sync();
    }
    bindCount('brand-short-desc', 'short-count');
    bindCount('brand-long-desc', 'long-count');

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
            document.getElementById('logo-current')?.classList.remove('is-removed');
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
