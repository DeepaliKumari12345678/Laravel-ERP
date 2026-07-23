@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Editing product '.$product->name : 'Add new product')

@section('content')
@php
    $selectedType = old('type', $product->type ?: 'product');
    $meta = is_array($product->meta) ? $product->meta : [];
    $summary = old('summary', $meta['summary'] ?? '');
    $metaTitle = old('meta_title', $meta['meta_title'] ?? '');
    $metaDescription = old('meta_description', $meta['meta_description'] ?? '');
    $currency = configuration('PS_CURRENCY_DEFAULT', 'INR');
    $taxEnabled = (string) configuration('PS_TAX_ENABLED', '0') === '1';
    $taxRate = (float) configuration('PS_TAX_RATE_DEFAULT', 0);
    $price = (float) old('price', $product->price ?? 0);
    $priceIncl = $taxEnabled ? round($price * (1 + ($taxRate / 100)), 2) : $price;
    $qty = (float) old('quantity', $product->quantity ?? 0);
    $oldPackItems = old('pack_items');
    if (! is_array($oldPackItems)) {
        $oldPackItems = $product->relationLoaded('packItems') || $product->exists
            ? $product->packItems->map(fn ($item) => [
                'item_product_id' => $item->item_product_id,
                'quantity' => $item->quantity,
            ])->all()
            : [];
    }
    if ($oldPackItems === []) {
        $oldPackItems = [['item_product_id' => '', 'quantity' => 1]];
    }

    $oldFeatures = old('product_features');
    if (! is_array($oldFeatures)) {
        $oldFeatures = $product->relationLoaded('features') || $product->exists
            ? $product->features->mapWithKeys(fn ($feature) => [
                $feature->id => [
                    'feature_id' => $feature->id,
                    'feature_value_id' => $feature->pivot->feature_value_id,
                ],
            ])->all()
            : [];
    }
    $selectedFeatureValues = collect($oldFeatures)
        ->mapWithKeys(function ($row, $key) {
            $featureId = (int) ($row['feature_id'] ?? $key);
            $valueId = $row['feature_value_id'] ?? $row;

            return $featureId > 0 && filled($valueId) ? [$featureId => (string) $valueId] : [];
        })
        ->all();

    $oldCombinations = old('combinations');
    if (! is_array($oldCombinations)) {
        $oldCombinations = $product->relationLoaded('combinations') || $product->exists
            ? $product->combinations->map(fn ($combo) => [
                'reference' => $combo->reference,
                'quantity' => $combo->quantity,
                'price_impact' => $combo->price_impact,
                'active' => $combo->active ? '1' : '0',
                'attribute_value_ids' => $combo->attributeValues->pluck('id')->all(),
            ])->all()
            : [];
    }
    if ($oldCombinations === []) {
        $oldCombinations = [['reference' => '', 'quantity' => 0, 'price_impact' => 0, 'active' => '1', 'attribute_value_ids' => []]];
    }

    $featuresCatalog = $featuresCatalog ?? collect();
    $attributeGroups = $attributeGroups ?? collect();
    $activeTab = old('_tab', request('tab', 'description'));
    if (in_array($activeTab, ['features', 'combinations'], true)) {
        $activeTab = 'details';
    }
@endphp

<style>
    .pf-page { padding-bottom: 5.5rem; }
    .pf-header {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1rem;
        align-items: start;
        margin-bottom: 1rem;
    }
    .pf-name-label {
        display: block; font-size: 0.78rem; font-weight: 600; color: var(--ps-muted);
        text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.35rem;
    }
    .pf-name-input {
        width: 100%; font-size: 1.45rem; font-weight: 600; padding: 0.65rem 0.85rem;
        border: 1px solid var(--ps-line); border-radius: 4px;
    }
    .pf-name-input:focus { outline: 0; border-color: var(--ps-blue); box-shadow: 0 0 0 2px rgba(37,185,215,0.15); }
    .pf-subrow {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-top: 0.65rem;
    }
    .pf-type-label { color: var(--ps-muted); font-size: 0.9rem; font-weight: 600; }
    .pf-online { display: inline-flex; align-items: center; gap: 0.5rem; }
    .pf-online .cu-switch { margin: 0; }
    .pf-online-text { font-size: 0.85rem; font-weight: 600; color: #3d8b4f; }
    .pf-online-text.off { color: var(--ps-muted); }
    .pf-badges {
        display: flex; flex-wrap: wrap; gap: 0.45rem; justify-content: flex-end; max-width: 420px;
    }
    .pf-badge {
        display: inline-flex; align-items: center; gap: 0.35rem;
        background: #fff; border: 1px solid var(--ps-line); border-radius: 999px;
        padding: 0.4rem 0.75rem; font-size: 0.8rem; color: var(--ps-ink); white-space: nowrap;
    }
    .pf-badge.stock-ok { background: #e8f5eb; border-color: #c5e4cb; color: #3d8b4f; font-weight: 600; }
    .pf-badge.stock-out { background: #fde8e6; border-color: #f3c5c0; color: var(--danger); font-weight: 600; }
    .pf-badge .muted { color: var(--ps-muted); font-weight: 500; }

    .pf-tabs {
        display: flex; gap: 0; border-bottom: 1px solid var(--ps-line);
        margin-bottom: 0; background: #fff; overflow-x: auto;
    }
    .pf-tab {
        flex: 0 0 auto; padding: 0.9rem 1.1rem; border: 0; background: none;
        color: var(--ps-muted); font: inherit; font-weight: 600; font-size: 0.9rem;
        border-bottom: 3px solid transparent; margin-bottom: -1px; cursor: pointer;
    }
    .pf-tab:hover { color: var(--ps-ink); }
    .pf-tab.active { color: var(--ps-ink); border-bottom-color: var(--ps-blue); }

    .pf-panel { display: none; background: #fff; border: 1px solid var(--ps-line); border-top: 0; border-radius: 0 0 4px 4px; padding: 1.25rem 1.35rem; }
    .pf-panel.active { display: block; }

    .pf-section-title { margin: 0 0 0.85rem; font-size: 1rem; font-weight: 600; }
    .pf-field { margin-bottom: 1.15rem; }
    .pf-field > label, .pf-field-label {
        display: block; font-weight: 600; color: var(--ps-ink); margin-bottom: 0.4rem; font-size: 0.9rem;
    }
    .pf-hint { color: var(--ps-muted); font-size: 0.78rem; margin-top: 0.35rem; }
    .pf-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .pf-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; }

    .pf-dropzone {
        border: 2px dashed #c5d4da; border-radius: 6px; background: #fafbfc;
        min-height: 180px; display: flex; flex-direction: column; align-items: center;
        justify-content: center; gap: 0.5rem; padding: 1.5rem; text-align: center;
        cursor: pointer; transition: border-color .15s, background .15s; position: relative;
    }
    .pf-dropzone:hover, .pf-dropzone.dragover { border-color: var(--ps-blue); background: #f4fbfd; }
    .pf-dropzone svg { width: 36px; height: 36px; color: #8a9ba3; }
    .pf-dropzone strong { color: var(--ps-ink); }
    .pf-dropzone .pf-hint { margin: 0; }
    .pf-dropzone input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .pf-preview-wrap { display: flex; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
    .pf-preview {
        width: 140px; height: 140px; object-fit: cover; border: 1px solid var(--ps-line);
        border-radius: 5px; background: #f4f6f7;
    }

    .cu-switch-row { display: flex; align-items: center; gap: 0.75rem; }
    .cu-switch {
        position: relative; width: 44px; height: 24px; border-radius: 12px; border: 0; cursor: pointer;
        background: #bbcdd2; transition: background .15s; flex-shrink: 0;
    }
    .cu-switch.on { background: #70b580; }
    .cu-switch::after {
        content: ''; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px;
        border-radius: 50%; background: #fff; transition: left .15s;
    }
    .cu-switch.on::after { left: 23px; }

    .pack-row {
        display: grid; grid-template-columns: 1fr 120px 42px; gap: 0.45rem; align-items: end;
        margin-bottom: 0.55rem;
    }
    .combo-row {
        border: 1px solid var(--ps-line); border-radius: 4px; background: #fafbfc;
        padding: 0.85rem; margin-bottom: 0.75rem;
    }
    .combo-grid {
        display: grid; grid-template-columns: minmax(0, 1.2fr) 8rem 7rem 7rem 42px; gap: 0.45rem; align-items: end;
    }
    .combo-attrs {
        display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem;
    }
    .combo-attrs label { flex: 1 1 150px; min-width: 130px; font-size: 0.82rem; font-weight: 600; }
    .pf-empty-hint { color: var(--ps-muted); font-size: 0.88rem; margin: 0 0 0.85rem; }
    .type-only { display: none; }
    .type-only.show { display: block; }

    .pf-footer {
        position: fixed; left: 210px; right: 0; bottom: 0; z-index: 40;
        display: flex; justify-content: space-between; align-items: center; gap: 1rem;
        padding: 0.85rem 1.25rem; background: #f4f6f7; border-top: 1px solid var(--ps-line);
        box-shadow: 0 -4px 16px rgba(0,0,0,.04);
    }
    .pf-footer-right { display: flex; gap: 0.5rem; align-items: center; }

    @media (max-width: 980px) {
        .pf-header { grid-template-columns: 1fr; }
        .pf-badges { justify-content: flex-start; max-width: none; }
        .pf-grid-2, .pf-grid-3 { grid-template-columns: 1fr; }
        .pf-footer { left: 0; }
        .pack-row, .combo-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="pf-page">
    <div class="ps-breadcrumb">
        <a href="{{ route('admin.catalog.products') }}">Products</a> &gt; {{ $mode === 'edit' ? 'Edit' : 'Add' }}
    </div>

    <form method="post" enctype="multipart/form-data" id="product-form"
          action="{{ $mode === 'edit' ? route('admin.catalog.products.update', $product) : route('admin.catalog.products.store') }}">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif
        <input type="hidden" name="_tab" id="active-tab-input" value="{{ $activeTab }}">

        <div class="pf-header">
            <div>
                <label class="pf-name-label" for="product-name">Product name</label>
                <input id="product-name" class="pf-name-input" name="name" value="{{ old('name', $product->name) }}" required placeholder="Enter product name">
                <div class="pf-subrow">
                    <span class="pf-type-label" id="pf-type-label">{{ $typeOptions[$selectedType] ?? 'Product' }}</span>
                    <div class="pf-online">
                        <button type="button" class="cu-switch {{ old('active', $product->active) ? 'on' : '' }}" data-switch="active" aria-label="Online status"></button>
                        <span class="pf-online-text {{ old('active', $product->active) ? '' : 'off' }}" id="online-label">
                            {{ old('active', $product->active) ? 'Online' : 'Offline' }}
                        </span>
                        <input type="hidden" name="active" value="{{ old('active', $product->active) ? '1' : '0' }}">
                    </div>
                </div>
            </div>
            <div class="pf-badges" id="pf-badges"
                 data-currency="{{ $currency }}"
                 data-tax-enabled="{{ $taxEnabled ? '1' : '0' }}"
                 data-tax-rate="{{ $taxRate }}">
                <span class="pf-badge" id="badge-price-excl">
                    <span class="muted">Price (tax excl.)</span>
                    <strong>{{ number_format($price, 2) }} {{ $currency }}</strong>
                </span>
                @if($taxEnabled)
                    <span class="pf-badge" id="badge-price-incl">
                        <span class="muted">Price (tax incl.)</span>
                        <strong>{{ number_format($priceIncl, 2) }} {{ $currency }}</strong>
                    </span>
                @endif
                <span class="pf-badge {{ $qty > 0 ? 'stock-ok' : 'stock-out' }}" id="badge-qty">
                    {{ $qty > 0 ? number_format($qty, 0).' in stock' : 'Out of stock' }}
                </span>
                <span class="pf-badge" id="badge-ref">
                    <span class="muted">Ref</span>
                    <strong>{{ old('sku', $product->sku) ?: '—' }}</strong>
                </span>
            </div>
        </div>

        <div class="pf-tabs" role="tablist">
            @foreach([
                'description' => 'Description',
                'details' => 'Details',
                'stocks' => 'Stocks',
                'shipping' => 'Shipping',
                'pricing' => 'Pricing',
                'seo' => 'SEO',
                'options' => 'Options',
            ] as $key => $label)
                <button type="button" class="pf-tab {{ $activeTab === $key ? 'active' : '' }}" data-tab="{{ $key }}" role="tab">{{ $label }}</button>
            @endforeach
        </div>

        {{-- Description --}}
        <div class="pf-panel {{ $activeTab === 'description' ? 'active' : '' }}" data-panel="description">
            <h3 class="pf-section-title">Images</h3>
            <div class="pf-field">
                <div class="pf-preview-wrap">
                    @if($product->image_url)
                        <img id="product-image-preview" class="pf-preview" src="{{ $product->image_url }}" alt="{{ $product->name }}">
                    @else
                        <img id="product-image-preview" class="pf-preview" src="" alt="Product preview" hidden>
                    @endif
                    <div class="pf-dropzone" id="product-dropzone">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                        <strong>Drop images here or select files</strong>
                        <div class="pf-hint">Recommended size: 800×800px. JPG, PNG or WebP · max 4 MB.</div>
                        <input id="product-image-input" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    </div>
                </div>
                @if($product->image_path)
                    <label style="display:flex;align-items:center;gap:.45rem;margin-top:.65rem;">
                        <input type="checkbox" name="remove_image" value="1" style="width:auto;"> Remove current image
                    </label>
                @endif
            </div>

            <h3 class="pf-section-title">Summary</h3>
            <div class="pf-field">
                <textarea id="product-summary" name="summary" rows="4" maxlength="800" placeholder="Short description shown in listings">{{ $summary }}</textarea>
                <div class="pf-hint"><span id="summary-count">{{ strlen((string) $summary) }}</span> of 800 characters allowed.</div>
            </div>

            <h3 class="pf-section-title">Description</h3>
            <div class="pf-field">
                <textarea name="description" rows="8" placeholder="Full product description">{{ old('description', $product->description) }}</textarea>
            </div>
        </div>

        {{-- Details --}}
        <div class="pf-panel {{ $activeTab === 'details' ? 'active' : '' }}" data-panel="details">
            <div class="pf-grid-2">
                <div class="pf-field">
                    <label for="product-type">Type <span style="color:var(--danger);">*</span></label>
                    <select id="product-type" name="type" required>
                        @foreach($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="pf-hint">Packs contain other products. Virtual products can include a downloadable file.</div>
                </div>
                <div class="pf-field">
                    <label for="product-sku">Reference / SKU @if((string) configuration('PS_PRODUCT_SKU_REQUIRED', '1') === '1')<span style="color:var(--danger);">*</span>@endif</label>
                    <input id="product-sku" name="sku" value="{{ old('sku', $product->sku) }}" @required((string) configuration('PS_PRODUCT_SKU_REQUIRED', '1') === '1')>
                    @if((string) configuration('PS_PRODUCT_SKU_REQUIRED', '1') !== '1')
                        <div class="pf-hint">Leave blank to generate an SKU automatically.</div>
                    @endif
                </div>
                <div class="pf-field">
                    <label>Category</label>
                    <select name="category_id">
                        <option value="">— None —</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pf-field">
                    <label>Brand</label>
                    <select name="brand_id">
                        <option value="">— None —</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product->brand_id) === (string) $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pf-field">
                    <label>Primary supplier</label>
                    <select name="supplier_id">
                        <option value="">— None —</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $product->supplier_id) === (string) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pf-field">
                    <label>Supplier reference</label>
                    <input name="supplier_sku" value="{{ old('supplier_sku', $product->supplier_sku) }}">
                </div>

                @forelse($featuresCatalog as $feature)
                    <div class="pf-field">
                        <label>{{ $feature->name }}</label>
                        <input type="hidden" name="product_features[{{ $feature->id }}][feature_id]" value="{{ $feature->id }}">
                        <select name="product_features[{{ $feature->id }}][feature_value_id]">
                            <option value="">— None —</option>
                            @foreach($feature->values as $featureValue)
                                <option value="{{ $featureValue->id }}" @selected(($selectedFeatureValues[$feature->id] ?? '') === (string) $featureValue->id)>
                                    {{ $featureValue->value }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @empty
                    <div class="pf-field" style="grid-column:1 / -1;">
                        <div class="pf-hint" style="margin:0;">
                            No features yet.
                            <a href="{{ route('admin.catalog.features.create') }}">Create features</a>
                            under Catalog → Attributes &amp; Features.
                        </div>
                    </div>
                @endforelse
            </div>
            @error('product_features')
                <div class="pf-hint" style="color:var(--danger);">{{ $message }}</div>
            @enderror

            <h3 class="pf-section-title" style="margin-top:1.5rem;">Combinations</h3>
            @if($attributeGroups->isEmpty())
                <p class="pf-empty-hint">
                    No attributes yet.
                    <a href="{{ route('admin.catalog.attributes.create') }}">Create attributes</a>
                    (Size, Color…) and values, then add combinations here.
                </p>
            @else
                <p class="pf-empty-hint">Each row is one sellable variant. Choose attribute values like Size / Color.</p>
                <div id="combination-rows">
                    @foreach($oldCombinations as $index => $combo)
                        @php $selectedValueIds = collect($combo['attribute_value_ids'] ?? [])->map(fn ($id) => (string) $id); @endphp
                        <div class="combo-row" data-combo-row>
                            <div class="combo-grid">
                                <label>Reference
                                    <input name="combinations[{{ $index }}][reference]" value="{{ $combo['reference'] ?? '' }}" placeholder="Optional SKU">
                                </label>
                                <label>Qty
                                    <input type="number" step="0.01" min="0" name="combinations[{{ $index }}][quantity]" value="{{ $combo['quantity'] ?? 0 }}">
                                </label>
                                <label>Price impact
                                    <input type="number" step="0.01" name="combinations[{{ $index }}][price_impact]" value="{{ $combo['price_impact'] ?? 0 }}">
                                </label>
                                <label>Enabled
                                    <select name="combinations[{{ $index }}][active]">
                                        <option value="1" @selected((string) ($combo['active'] ?? '1') === '1')>Yes</option>
                                        <option value="0" @selected((string) ($combo['active'] ?? '1') === '0')>No</option>
                                    </select>
                                </label>
                                <button type="button" class="btn btn-ghost combo-remove" title="Remove">×</button>
                            </div>
                            <div class="combo-attrs">
                                @foreach($attributeGroups as $group)
                                    @php
                                        $groupSelected = $group->values->first(fn ($v) => $selectedValueIds->contains((string) $v->id));
                                    @endphp
                                    <label>{{ $group->public_name ?: $group->name }}
                                        <select name="combinations[{{ $index }}][attribute_value_ids][]" data-attr-group="{{ $group->id }}">
                                            <option value="">— None —</option>
                                            @foreach($group->values as $attrValue)
                                                <option value="{{ $attrValue->id }}" @selected($groupSelected && (int) $groupSelected->id === (int) $attrValue->id)>
                                                    {{ $attrValue->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" id="combo-add" class="btn btn-ghost">+ Add combination</button>
                @error('combinations')
                    <div class="pf-hint" style="color:var(--danger);">{{ $message }}</div>
                @enderror
            @endif
        </div>

        {{-- Stocks --}}
        <div class="pf-panel {{ $activeTab === 'stocks' ? 'active' : '' }}" data-panel="stocks">
            <div id="section-inventory" class="type-only">
                <div class="pf-grid-2">
                    <div class="pf-field">
                        <label for="product-qty">Quantity</label>
                        <input id="product-qty" type="number" step="0.01" name="quantity" value="{{ old('quantity', $product->quantity) }}">
                        <div class="pf-hint">Use the Stock page for later adjustments.</div>
                    </div>
                    <div class="pf-field">
                        <div class="pf-field-label">Track inventory</div>
                        <div class="cu-switch-row">
                            <button type="button" class="cu-switch {{ old('track_inventory', $product->track_inventory) ? 'on' : '' }}" data-switch="track_inventory"></button>
                            <span class="switch-text">{{ old('track_inventory', $product->track_inventory) ? 'Yes' : 'No' }}</span>
                            <input type="hidden" name="track_inventory" value="{{ old('track_inventory', $product->track_inventory) ? '1' : '0' }}">
                        </div>
                    </div>
                </div>
            </div>

            <div id="section-pack" class="type-only">
                <h3 class="pf-section-title">Pack products</h3>
                <div id="pack-items">
                    @foreach($oldPackItems as $index => $packItem)
                        <div class="pack-row">
                            <label>Product
                                <select name="pack_items[{{ $index }}][item_product_id]">
                                    <option value="">— Select product —</option>
                                    @foreach($packProducts as $candidate)
                                        <option value="{{ $candidate->id }}" @selected((string) ($packItem['item_product_id'] ?? '') === (string) $candidate->id)>
                                            {{ $candidate->name }} ({{ $candidate->sku }})
                                            @if($candidate->track_inventory) — qty {{ number_format((float) $candidate->quantity, 0) }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Qty
                                <input type="number" step="0.01" min="0.01" name="pack_items[{{ $index }}][quantity]" value="{{ $packItem['quantity'] ?? 1 }}">
                            </label>
                            <button type="button" class="btn btn-ghost pack-remove" title="Remove">×</button>
                        </div>
                    @endforeach
                </div>
                <button type="button" id="pack-add" class="btn btn-ghost">+ Add product to pack</button>
                @error('pack_items')
                    <div class="pf-hint" style="color:var(--danger);">{{ $message }}</div>
                @enderror
            </div>

            <div id="stocks-virtual-note" class="type-only">
                <p class="pf-hint" style="margin:0;">Virtual products do not track physical stock.</p>
            </div>
        </div>

        {{-- Shipping --}}
        <div class="pf-panel {{ $activeTab === 'shipping' ? 'active' : '' }}" data-panel="shipping">
            <div id="section-shipping" class="type-only">
                <div class="pf-field">
                    <label>Shipping weight ({{ configuration('PS_PRODUCT_WEIGHT_UNIT', 'kg') }})</label>
                    <input type="number" step="0.001" min="0" name="weight" value="{{ old('weight', $product->weight ?? 0) }}">
                </div>
                <div class="pf-field">
                    <div class="pf-field-label">Package dimensions ({{ configuration('PS_PRODUCT_DIMENSION_UNIT', 'cm') }})</div>
                    <div class="pf-grid-3">
                        <label>Width<input type="number" step="0.01" min="0" name="width" value="{{ old('width', $product->width ?? 0) }}"></label>
                        <label>Height<input type="number" step="0.01" min="0" name="height" value="{{ old('height', $product->height ?? 0) }}"></label>
                        <label>Depth<input type="number" step="0.01" min="0" name="depth" value="{{ old('depth', $product->depth ?? 0) }}"></label>
                    </div>
                </div>
            </div>
            <div id="shipping-virtual-note" class="type-only">
                <p class="pf-hint" style="margin:0;">Shipping dimensions are not used for virtual products.</p>
            </div>
        </div>

        {{-- Pricing --}}
        <div class="pf-panel {{ $activeTab === 'pricing' ? 'active' : '' }}" data-panel="pricing">
            <div class="pf-grid-2">
                <div class="pf-field">
                    <label for="product-price">Price (tax excl.) <span style="color:var(--danger);">*</span></label>
                    <input id="product-price" type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}" required>
                </div>
                <div class="pf-field">
                    <label>Cost price</label>
                    <input type="number" step="0.01" min="0" name="cost" value="{{ old('cost', $product->cost) }}">
                </div>
            </div>
        </div>

        {{-- SEO --}}
        <div class="pf-panel {{ $activeTab === 'seo' ? 'active' : '' }}" data-panel="seo">
            <div class="pf-field">
                <label>Friendly URL</label>
                <input type="text" value="{{ $product->slug ?: '—' }}" disabled>
                <div class="pf-hint">Generated automatically from the product name when saved.</div>
            </div>
            <div class="pf-field">
                <label>Meta title</label>
                <input name="meta_title" value="{{ $metaTitle }}" maxlength="255" placeholder="Page title for search engines">
            </div>
            <div class="pf-field">
                <label>Meta description</label>
                <textarea name="meta_description" rows="3" maxlength="512" placeholder="Short description for search results">{{ $metaDescription }}</textarea>
            </div>
        </div>

        {{-- Options --}}
        <div class="pf-panel {{ $activeTab === 'options' ? 'active' : '' }}" data-panel="options">
            <div id="section-virtual" class="type-only">
                <h3 class="pf-section-title">Virtual product file</h3>
                <div class="pf-field">
                    @if($product->virtual_file_name)
                        <div style="margin-bottom:.55rem;"><strong>Current file:</strong> {{ $product->virtual_file_name }}</div>
                        <label style="display:flex;align-items:center;gap:.45rem;margin-bottom:.65rem;">
                            <input type="checkbox" name="remove_virtual_file" value="1" style="width:auto;"> Remove current file
                        </label>
                    @endif
                    <input type="file" name="virtual_file">
                    <div class="pf-hint">Stored privately. Maximum 50 MB.</div>
                </div>
                <div class="pf-grid-2">
                    <div class="pf-field">
                        <label>Download limit</label>
                        <input type="number" min="0" name="download_limit" value="{{ old('download_limit', $product->download_limit) }}" placeholder="Unlimited">
                    </div>
                    <div class="pf-field">
                        <label>Access days</label>
                        <input type="number" min="0" name="download_expiry_days" value="{{ old('download_expiry_days', $product->download_expiry_days) }}" placeholder="Unlimited">
                    </div>
                </div>
                <div class="pf-field">
                    <label>Expires on</label>
                    <input type="date" name="download_expires_at" value="{{ old('download_expires_at', $product->download_expires_at?->format('Y-m-d')) }}">
                </div>
            </div>
            <div id="options-other-note" class="type-only">
                <p class="pf-hint" style="margin:0;">Additional options for this product type appear here when relevant (e.g. virtual downloads).</p>
            </div>
        </div>

        <div class="pf-footer no-print">
            <a href="{{ route('admin.catalog.products') }}" class="btn btn-ghost">← Go to catalog</a>
            <div class="pf-footer-right">
                <a href="{{ route('admin.catalog.products') }}" class="btn btn-ghost">Cancel</a>
                <button class="btn btn-primary" type="submit">Save and publish</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('product-form');
    if (!form) return;

    const typeSelect = document.getElementById('product-type');
    const typeLabel = document.getElementById('pf-type-label');
    const tabInput = document.getElementById('active-tab-input');
    const badges = document.getElementById('pf-badges');
    const currency = badges?.dataset.currency || 'INR';
    const taxEnabled = badges?.dataset.taxEnabled === '1';
    const taxRate = parseFloat(badges?.dataset.taxRate || '0') || 0;

    // Switches
    document.querySelectorAll('[data-switch]').forEach(btn => {
        btn.addEventListener('click', () => {
            const name = btn.dataset.switch;
            const input = btn.parentElement.querySelector(`input[name="${name}"]`);
            const text = btn.parentElement.querySelector('.switch-text, .pf-online-text');
            const on = input.value !== '1';
            input.value = on ? '1' : '0';
            btn.classList.toggle('on', on);
            if (text) {
                if (name === 'active') {
                    text.textContent = on ? 'Online' : 'Offline';
                    text.classList.toggle('off', !on);
                } else {
                    text.textContent = on ? 'Yes' : 'No';
                }
            }
        });
    });

    // Tabs
    function activateTab(key) {
        document.querySelectorAll('.pf-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === key));
        document.querySelectorAll('.pf-panel').forEach(p => p.classList.toggle('active', p.dataset.panel === key));
        if (tabInput) tabInput.value = key;
        if (history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', key);
            history.replaceState({}, '', url);
        }
    }
    document.querySelectorAll('.pf-tab').forEach(btn => {
        btn.addEventListener('click', () => activateTab(btn.dataset.tab));
    });

    // Type sections
    function syncTypeSections() {
        const type = typeSelect?.value || 'product';
        if (typeLabel && typeSelect) {
            typeLabel.textContent = typeSelect.options[typeSelect.selectedIndex]?.text || type;
        }
        const show = (id, cond) => {
            const el = document.getElementById(id);
            if (el) el.classList.toggle('show', !!cond);
        };
        show('section-inventory', type === 'product' || type === 'service' || type === 'pack');
        show('section-shipping', type !== 'virtual');
        show('shipping-virtual-note', type === 'virtual');
        show('section-pack', type === 'pack');
        show('section-virtual', type === 'virtual');
        show('options-other-note', type !== 'virtual');
        show('stocks-virtual-note', type === 'virtual');
    }
    typeSelect?.addEventListener('change', syncTypeSections);
    syncTypeSections();

    // Live badges
    function fmt(n) {
        return (Math.round(n * 100) / 100).toFixed(2);
    }
    function refreshBadges() {
        const price = parseFloat(document.getElementById('product-price')?.value || '0') || 0;
        const qty = parseFloat(document.getElementById('product-qty')?.value || '0') || 0;
        const sku = document.getElementById('product-sku')?.value?.trim() || '—';
        const excl = document.getElementById('badge-price-excl');
        if (excl) excl.innerHTML = `<span class="muted">Price (tax excl.)</span> <strong>${fmt(price)} ${currency}</strong>`;
        const incl = document.getElementById('badge-price-incl');
        if (incl && taxEnabled) {
            const withTax = price * (1 + taxRate / 100);
            incl.innerHTML = `<span class="muted">Price (tax incl.)</span> <strong>${fmt(withTax)} ${currency}</strong>`;
        }
        const qtyBadge = document.getElementById('badge-qty');
        if (qtyBadge) {
            qtyBadge.className = 'pf-badge ' + (qty > 0 ? 'stock-ok' : 'stock-out');
            qtyBadge.textContent = qty > 0 ? `${Math.round(qty)} in stock` : 'Out of stock';
        }
        const ref = document.getElementById('badge-ref');
        if (ref) ref.innerHTML = `<span class="muted">Ref</span> <strong>${sku}</strong>`;
    }
    ['product-price', 'product-qty', 'product-sku'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', refreshBadges);
    });

    // Summary counter
    const summary = document.getElementById('product-summary');
    const summaryCount = document.getElementById('summary-count');
    summary?.addEventListener('input', () => {
        if (summaryCount) summaryCount.textContent = String(summary.value.length);
    });

    // Image dropzone
    const input = document.getElementById('product-image-input');
    const preview = document.getElementById('product-image-preview');
    const dropzone = document.getElementById('product-dropzone');
    function showPreview(file) {
        if (!file || !preview) return;
        preview.src = URL.createObjectURL(file);
        preview.hidden = false;
    }
    input?.addEventListener('change', e => showPreview(e.target.files?.[0]));
    ['dragenter', 'dragover'].forEach(ev => {
        dropzone?.addEventListener(ev, e => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });
    });
    ['dragleave', 'drop'].forEach(ev => {
        dropzone?.addEventListener(ev, e => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
        });
    });
    dropzone?.addEventListener('drop', e => {
        const file = e.dataTransfer?.files?.[0];
        if (!file || !input) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        showPreview(file);
    });

    // Pack rows
    @php
        $packProductOptions = $packProducts->map(fn ($p) => [
            'id' => $p->id,
            'label' => $p->name.' ('.$p->sku.')'.($p->track_inventory ? ' — qty '.number_format((float) $p->quantity, 0) : ''),
        ])->values();
    @endphp
    const packItems = document.getElementById('pack-items');
    const packProducts = @json($packProductOptions);

    function packRowHtml(index) {
        const options = packProducts.map(product => `<option value="${product.id}">${product.label}</option>`).join('');
        return `<div class="pack-row">
            <label>Product
                <select name="pack_items[${index}][item_product_id]">
                    <option value="">— Select product —</option>
                    ${options}
                </select>
            </label>
            <label>Qty
                <input type="number" step="0.01" min="0.01" name="pack_items[${index}][quantity]" value="1">
            </label>
            <button type="button" class="btn btn-ghost pack-remove" title="Remove">×</button>
        </div>`;
    }

    document.getElementById('pack-add')?.addEventListener('click', () => {
        const index = packItems.querySelectorAll('.pack-row').length;
        packItems.insertAdjacentHTML('beforeend', packRowHtml(index));
    });

    packItems?.addEventListener('click', event => {
        if (!event.target.classList.contains('pack-remove')) return;
        const rows = packItems.querySelectorAll('.pack-row');
        if (rows.length <= 1) return;
        event.target.closest('.pack-row')?.remove();
    });

    // Combinations
    @php
        $attrGroupsJs = $attributeGroups->map(fn ($g) => [
            'id' => $g->id,
            'name' => $g->public_name ?: $g->name,
            'values' => $g->values->map(fn ($v) => ['id' => $v->id, 'name' => $v->name])->values(),
        ])->values();
    @endphp
    const attributeGroupsData = @json($attrGroupsJs);
    const comboRows = document.getElementById('combination-rows');

    function comboRowHtml(index) {
        const attrs = attributeGroupsData.map(group => {
            const opts = group.values.map(v => `<option value="${v.id}">${v.name}</option>`).join('');
            return `<label>${group.name}
                <select name="combinations[${index}][attribute_value_ids][]" data-attr-group="${group.id}">
                    <option value="">— None —</option>${opts}
                </select>
            </label>`;
        }).join('');

        return `<div class="combo-row" data-combo-row>
            <div class="combo-grid">
                <label>Reference<input name="combinations[${index}][reference]" placeholder="Optional SKU"></label>
                <label>Qty<input type="number" step="0.01" min="0" name="combinations[${index}][quantity]" value="0"></label>
                <label>Price impact<input type="number" step="0.01" name="combinations[${index}][price_impact]" value="0"></label>
                <label>Enabled
                    <select name="combinations[${index}][active]">
                        <option value="1" selected>Yes</option>
                        <option value="0">No</option>
                    </select>
                </label>
                <button type="button" class="btn btn-ghost combo-remove" title="Remove">×</button>
            </div>
            <div class="combo-attrs">${attrs}</div>
        </div>`;
    }

    document.getElementById('combo-add')?.addEventListener('click', () => {
        const index = comboRows.querySelectorAll('[data-combo-row]').length;
        comboRows.insertAdjacentHTML('beforeend', comboRowHtml(index));
    });

    comboRows?.addEventListener('click', event => {
        if (!event.target.classList.contains('combo-remove')) return;
        const rows = comboRows.querySelectorAll('[data-combo-row]');
        if (rows.length <= 1) {
            const row = rows[0];
            row.querySelectorAll('input').forEach(i => { if (i.type === 'number') i.value = '0'; else i.value = ''; });
            row.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
            return;
        }
        event.target.closest('[data-combo-row]')?.remove();
    });
})();
</script>
@endpush
