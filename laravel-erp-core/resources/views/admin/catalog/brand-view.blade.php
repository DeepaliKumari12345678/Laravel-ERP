@extends('admin.layouts.app')

@section('title', 'Brand '.$brand->name)

@section('content')
<style>
    .bv-card { max-width: 920px; margin: 0 auto; }
    .bv-grid { display:grid; grid-template-columns:140px 1fr; gap:1rem; align-items:start; }
    .bv-logo {
        width:120px; height:120px; object-fit:contain; border:1px solid var(--ps-line);
        border-radius:4px; background:#f4f6f7;
    }
    .bv-meta { display:grid; gap:0.55rem; }
    .bv-meta div { display:flex; gap:0.75rem; }
    .bv-meta span { color:var(--ps-muted); min-width:7rem; }
    @media (max-width:720px) { .bv-grid { grid-template-columns:1fr; } }
</style>

<div class="ps-breadcrumb">
    <a href="{{ route('admin.catalog.brands') }}">Brands &amp; Suppliers</a> &gt; Brands &gt; {{ $brand->name }}
</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">{{ $brand->name }}</h1>
    <div class="actions">
        <a href="{{ route('admin.catalog.brands') }}" class="btn btn-ghost">← Back</a>
        <a href="{{ route('admin.catalog.brands.edit', $brand) }}" class="btn btn-primary">Edit</a>
    </div>
</div>

<div class="card bv-card">
    <div class="card-head"><h3 style="margin:0;">Brand details</h3></div>
    <div class="bv-grid">
        <div>
            @if($brand->logo_url)
                <img src="{{ $brand->logo_url }}" alt="" class="bv-logo">
            @else
                <div class="bv-logo" style="display:grid;place-items:center;color:var(--ps-muted);">No logo</div>
            @endif
        </div>
        <div class="bv-meta">
            <div><span>ID</span><strong>{{ $brand->id }}</strong></div>
            <div><span>Name</span><strong>{{ $brand->name }}</strong></div>
            <div><span>Website</span><strong>
                @if($brand->website)
                    <a href="{{ $brand->website }}" target="_blank" rel="noopener" style="color:var(--ps-blue-dark);">{{ $brand->website }}</a>
                @else
                    —
                @endif
            </strong></div>
            <div><span>Products</span><strong>{{ $brand->products_count }}</strong></div>
            <div><span>Addresses</span><strong>{{ $brand->addresses_count }}</strong></div>
            <div><span>Enabled</span><strong>{{ $brand->active ? 'Yes' : 'No' }}</strong></div>
            <div><span>Description</span><strong>{{ $brand->description ?: '—' }}</strong></div>
        </div>
    </div>
</div>
@endsection
