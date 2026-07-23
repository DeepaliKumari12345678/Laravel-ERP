@extends('admin.layouts.app')

@section('title', 'Supplier '.$supplier->name)

@section('content')
<style>
    .sv-card { max-width: 920px; margin: 0 auto; }
    .sv-grid { display:grid; grid-template-columns:140px 1fr; gap:1rem; align-items:start; }
    .sv-logo {
        width:120px; height:120px; object-fit:contain; border:1px solid var(--ps-line);
        border-radius:4px; background:#f4f6f7;
    }
    .sv-meta { display:grid; gap:0.55rem; }
    .sv-meta div { display:flex; gap:0.75rem; }
    .sv-meta span { color:var(--ps-muted); min-width:8rem; }
    @media (max-width:720px) { .sv-grid { grid-template-columns:1fr; } }
</style>

<div class="ps-breadcrumb">
    <a href="{{ route('admin.catalog.suppliers') }}">Brands &amp; Suppliers</a> &gt; Suppliers &gt; {{ $supplier->name }}
</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">{{ $supplier->name }}</h1>
    <div class="actions">
        <a href="{{ route('admin.catalog.suppliers') }}" class="btn btn-ghost">← Back</a>
        <a href="{{ route('admin.catalog.suppliers.edit', $supplier) }}" class="btn btn-primary">Edit</a>
    </div>
</div>

<div class="card sv-card">
    <div class="card-head"><h3 style="margin:0;">Supplier details</h3></div>
    <div class="sv-grid">
        <div>
            @if($supplier->logo_url)
                <img src="{{ $supplier->logo_url }}" alt="" class="sv-logo">
            @else
                <div class="sv-logo" style="display:grid;place-items:center;color:var(--ps-muted);">No logo</div>
            @endif
        </div>
        <div class="sv-meta">
            <div><span>ID</span><strong>{{ $supplier->id }}</strong></div>
            <div><span>Name</span><strong>{{ $supplier->name }}</strong></div>
            <div><span>Contact</span><strong>{{ $supplier->contact_name ?: '—' }}</strong></div>
            <div><span>Email</span><strong>{{ $supplier->email ?: '—' }}</strong></div>
            <div><span>Phone</span><strong>{{ $supplier->phone ?: '—' }}</strong></div>
            <div><span>Products</span><strong>{{ $supplier->products_count }}</strong></div>
            <div><span>Location</span><strong>{{ collect([$supplier->city, $supplier->country])->filter()->implode(', ') ?: '—' }}</strong></div>
            <div><span>Enabled</span><strong>{{ $supplier->active ? 'Yes' : 'No' }}</strong></div>
        </div>
    </div>
</div>
@endsection
