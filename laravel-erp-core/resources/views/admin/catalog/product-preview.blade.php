@extends('admin.layouts.app')

@section('title', 'Preview · '.$product->name)

@section('content')
<div class="ps-breadcrumb">
    <a href="{{ route('admin.catalog.products') }}">Products</a> &gt; Preview
</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">{{ $product->name }}</h1>
    <div class="actions">
        <a href="{{ route('admin.catalog.products') }}" class="btn btn-ghost">← Back</a>
        <a href="{{ route('admin.catalog.products.edit', $product) }}" class="btn btn-primary">Edit</a>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3 style="margin-top:0;">Product image</h3>
        @if($product->image_url)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" style="max-width:100%;max-height:320px;border:1px solid var(--ps-line);border-radius:4px;">
        @else
            <div style="padding:3rem;text-align:center;background:#f4f6f7;border:1px solid var(--ps-line);border-radius:4px;color:var(--ps-muted);">
                No image available
            </div>
        @endif
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Details</h3>
        <div class="stat-line"><span>ID</span><strong>{{ $product->id }}</strong></div>
        <div class="stat-line"><span>Reference</span><strong>{{ $product->sku ?: '—' }}</strong></div>
        <div class="stat-line"><span>Type</span><strong>{{ $typeOptions[$product->type] ?? ucfirst($product->type) }}</strong></div>
        <div class="stat-line"><span>Category</span><strong>{{ $product->category?->name ?? '—' }}</strong></div>
        <div class="stat-line"><span>Brand</span><strong>{{ $product->brand?->name ?? '—' }}</strong></div>
        <div class="stat-line"><span>Supplier</span><strong>{{ $product->supplier?->name ?? '—' }}</strong></div>
        <div class="stat-line"><span>Price</span><strong>{{ number_format((float) $product->price, 2) }} {{ $currency }}</strong></div>
        <div class="stat-line"><span>Quantity</span><strong>{{ $product->track_inventory ? number_format((float) $product->quantity, 0) : '—' }}</strong></div>
        <div class="stat-line"><span>Status</span><strong>{{ $product->active ? 'Enabled' : 'Disabled' }}</strong></div>
    </div>
</div>

@if($product->description)
    <div class="card" style="margin-top:1rem;">
        <h3 style="margin-top:0;">Description</h3>
        <div style="white-space:pre-wrap;line-height:1.5;">{{ $product->description }}</div>
    </div>
@endif

@if($product->isPack() && $product->packItems->isNotEmpty())
    <div class="card" style="margin-top:1rem;">
        <h3 style="margin-top:0;">Pack items</h3>
        <table>
            <thead><tr><th>Product</th><th>SKU</th><th>Qty</th></tr></thead>
            <tbody>
            @foreach($product->packItems as $item)
                <tr>
                    <td>{{ $item->item?->name ?? '—' }}</td>
                    <td>{{ $item->item?->sku ?? '—' }}</td>
                    <td>{{ number_format((float) $item->quantity, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

@if($product->features->isNotEmpty())
    <div class="card" style="margin-top:1rem;">
        <h3 style="margin-top:0;">Features</h3>
        <table>
            <thead><tr><th>Feature</th><th>Value</th></tr></thead>
            <tbody>
            @foreach($product->features as $feature)
                <tr>
                    <td>{{ $feature->name }}</td>
                    <td>{{ $featureValues[$feature->pivot->feature_value_id]->value ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

@if($product->combinations->isNotEmpty())
    <div class="card" style="margin-top:1rem;">
        <h3 style="margin-top:0;">Combinations</h3>
        <table>
            <thead><tr><th>Variant</th><th>Reference</th><th>Qty</th><th>Price impact</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($product->combinations as $combo)
                <tr>
                    <td>{{ $combo->label() }}</td>
                    <td>{{ $combo->reference ?: '—' }}</td>
                    <td>{{ number_format((float) $combo->quantity, 0) }}</td>
                    <td>{{ number_format((float) $combo->price_impact, 2) }} {{ $currency }}</td>
                    <td>{{ $combo->active ? 'Enabled' : 'Disabled' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
