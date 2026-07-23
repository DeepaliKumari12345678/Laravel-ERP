@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Edit group '.$group->name : 'Add new group')

@section('content')
@php
    $meta = is_array($group->meta) ? $group->meta : [];
    $categoryDiscounts = old('category_discounts', $meta['category_discounts'] ?? []);
    if (! is_array($categoryDiscounts)) {
        $categoryDiscounts = [];
    }
@endphp

<style>
    .cg-tabs { display:flex; gap:0; border-bottom:1px solid var(--ps-line); margin-bottom:1.15rem; }
    .cg-tabs a {
        padding:0.7rem 1.1rem; text-decoration:none; color:var(--ps-muted); font-weight:600;
        border-bottom:2px solid transparent; margin-bottom:-1px;
    }
    .cg-tabs a.active { color:var(--ps-ink); border-bottom-color:#25b9d7; }
    .gf-wrap { max-width: 980px; margin: 0 auto; }
    .gf-row {
        display:grid; grid-template-columns: 220px minmax(0,1fr); gap:1.15rem; align-items:start;
        padding:1rem 0; border-bottom:1px solid #f0f2f4;
    }
    .gf-row:last-of-type { border-bottom:0; }
    .gf-label { font-weight:600; color:var(--ps-ink); padding-top:0.5rem; text-align:right; }
    .gf-label .req { color:var(--danger); }
    .gf-hint { color:var(--ps-muted); font-size:0.78rem; margin-top:0.4rem; font-style:italic; }
    .gf-discount {
        display:flex; align-items:stretch; max-width:220px;
        border:1px solid var(--ps-line); border-radius:3px; overflow:hidden; background:#fff;
    }
    .gf-discount input { border:0; border-radius:0; }
    .gf-discount span {
        display:inline-flex; align-items:center; padding:0 0.75rem; background:#f4f6f7;
        border-left:1px solid var(--ps-line); color:var(--ps-muted); font-weight:600;
    }
    .gf-switch-row { display:flex; align-items:center; gap:0.75rem; padding-top:0.2rem; }
    .gf-switch {
        position:relative; width:44px; height:24px; border-radius:12px; border:0; cursor:pointer;
        background:#bbcdd2; transition:background .15s; flex-shrink:0;
    }
    .gf-switch.on { background:#70b580; }
    .gf-switch::after {
        content:''; position:absolute; top:3px; left:3px; width:18px; height:18px;
        border-radius:50%; background:#fff; transition:left .15s;
    }
    .gf-switch.on::after { left:23px; }
    .gf-cat-list { display:grid; gap:0.55rem; margin-top:0.65rem; }
    .gf-cat-row {
        display:grid; grid-template-columns:1fr 120px auto; gap:0.45rem; align-items:center;
    }
    .gf-actions {
        display:flex; justify-content:space-between; gap:1rem; margin-top:1.35rem;
        max-width:980px; margin-left:auto; margin-right:auto;
    }
    @media (max-width:720px) {
        .gf-row { grid-template-columns:1fr; }
        .gf-label { text-align:left; }
        .gf-cat-row { grid-template-columns:1fr; }
    }
</style>

<div class="ps-breadcrumb">Customer Settings &gt; Groups</div>

<div style="margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">Groups</h1>
</div>

<div class="cg-tabs">
    <a href="{{ route('admin.settings.group', ['group' => 'customers']) }}">Customer Settings</a>
    <a href="{{ route('admin.customer-groups.index') }}" class="active">Groups</a>
    <a href="{{ route('admin.customer-titles.index') }}">Titles</a>
</div>

<form method="post"
      action="{{ $mode === 'edit' ? route('admin.customer-groups.update', $group) : route('admin.customer-groups.store') }}">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif

    <div class="card gf-wrap">
        <div class="card-head"><h3 style="margin:0;">Customer group</h3></div>

        <div class="gf-row">
            <div class="gf-label">Name <span class="req">*</span></div>
            <div>
                <input name="name" value="{{ old('name', $group->name) }}" required maxlength="100">
            </div>
        </div>

        <div class="gf-row">
            <div class="gf-label">Discount</div>
            <div>
                <div class="gf-discount">
                    <input type="number" step="0.01" min="0" max="100" name="discount_percent"
                           value="{{ old('discount_percent', $group->discount_percent ?? 0) }}" required>
                    <span>%</span>
                </div>
            </div>
        </div>

        <div class="gf-row">
            <div class="gf-label">Price display method</div>
            <div>
                <select name="price_display_method" style="max-width:280px;">
                    <option value="tax_excluded" @selected(old('price_display_method', $group->price_display_method) === 'tax_excluded')>Tax excluded</option>
                    <option value="tax_included" @selected(old('price_display_method', $group->price_display_method) === 'tax_included')>Tax included</option>
                </select>
            </div>
        </div>

        <div class="gf-row">
            <div class="gf-label">Show prices</div>
            <div>
                <div class="gf-switch-row">
                    <input type="hidden" name="show_prices" value="0">
                    <button type="button" class="gf-switch {{ old('show_prices', $group->show_prices) ? 'on' : '' }}"
                            id="show-prices-switch" aria-label="Toggle show prices"></button>
                    <input type="checkbox" name="show_prices" value="1" id="show-prices"
                           style="display:none;" @checked(old('show_prices', $group->show_prices))>
                    <span id="show-prices-label">{{ old('show_prices', $group->show_prices) ? 'Yes' : 'No' }}</span>
                </div>
                <div class="gf-hint">Need to hide prices for all groups? Save time, enable catalog mode in Product Settings instead.</div>
            </div>
        </div>

        <div class="gf-row">
            <div class="gf-label">Category discount</div>
            <div>
                <button type="button" class="btn btn-ghost" id="add-category-discount">Add a category discount</button>
                <div class="gf-cat-list" id="category-discount-list">
                    @foreach($categoryDiscounts as $i => $row)
                        <div class="gf-cat-row">
                            <select name="category_discounts[{{ $i }}][category_id]">
                                <option value="">— Category —</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) ($row['category_id'] ?? '') === (string) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="gf-discount" style="max-width:none;">
                                <input type="number" step="0.01" min="0" max="100" name="category_discounts[{{ $i }}][discount]"
                                       value="{{ $row['discount'] ?? 0 }}">
                                <span>%</span>
                            </div>
                            <button type="button" class="btn btn-ghost remove-cat-discount">Remove</button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    <div class="gf-actions">
        <a href="{{ route('admin.customer-groups.index') }}" class="btn btn-ghost">Cancel</a>
        <button class="btn btn-primary" type="submit">Save</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    function bindSwitch(btn, input, label) {
        if (!btn || !input) return;
        btn.addEventListener('click', () => {
            input.checked = !input.checked;
            btn.classList.toggle('on', input.checked);
            if (label) label.textContent = input.checked ? 'Yes' : 'No';
        });
    }

    bindSwitch(
        document.getElementById('show-prices-switch'),
        document.getElementById('show-prices'),
        document.getElementById('show-prices-label')
    );

    const list = document.getElementById('category-discount-list');
    const addBtn = document.getElementById('add-category-discount');
    const categoryOptions = @json($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values());

    function reindex() {
        Array.from(list.children).forEach((row, i) => {
            const select = row.querySelector('select');
            const input = row.querySelector('input[type="number"]');
            if (select) select.name = 'category_discounts['+i+'][category_id]';
            if (input) input.name = 'category_discounts['+i+'][discount]';
        });
    }

    function bindRemove(btn) {
        btn.addEventListener('click', () => {
            btn.closest('.gf-cat-row')?.remove();
            reindex();
        });
    }

    document.querySelectorAll('.remove-cat-discount').forEach(bindRemove);

    addBtn?.addEventListener('click', () => {
        const i = list.children.length;
        const row = document.createElement('div');
        row.className = 'gf-cat-row';
        const options = categoryOptions.map((c) => '<option value="'+c.id+'">'+c.name+'</option>').join('');
        row.innerHTML =
            '<select name="category_discounts['+i+'][category_id]"><option value="">— Category —</option>'+options+'</select>' +
            '<div class="gf-discount" style="max-width:none;"><input type="number" step="0.01" min="0" max="100" name="category_discounts['+i+'][discount]" value="0"><span>%</span></div>' +
            '<button type="button" class="btn btn-ghost remove-cat-discount">Remove</button>';
        list.appendChild(row);
        bindRemove(row.querySelector('.remove-cat-discount'));
    });
})();
</script>
@endpush
