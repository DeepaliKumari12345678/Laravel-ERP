@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Editing feature '.$feature->name : 'Add new feature')

@section('content')
<style>
    .af-tabs { display:flex; gap:0; border-bottom:1px solid var(--ps-line); margin-bottom:1.25rem; }
    .af-tabs a {
        padding:0.7rem 1.1rem; text-decoration:none; color:var(--ps-muted); font-weight:600;
        border-bottom:2px solid transparent; margin-bottom:-1px;
    }
    .af-tabs a.active { color:var(--ps-ink); border-bottom-color:#25b9d7; }
    .bf-wrap { max-width: 720px; margin: 0 auto; }
    .bf-row {
        display: grid; grid-template-columns: 180px minmax(0, 1fr); gap: 1.25rem; align-items: start;
        padding: 1.1rem 0; border-bottom: 1px solid #f0f2f4;
    }
    .bf-row:last-of-type { border-bottom: 0; }
    .bf-label { font-weight: 600; color: var(--ps-ink); padding-top: 0.55rem; text-align: right; }
    .bf-label .req { color: var(--danger); }
    .bf-hint { color: var(--ps-muted); font-size: 0.78rem; margin-top: 0.35rem; }
    .bf-actions {
        display: flex; justify-content: space-between; gap: 1rem; margin-top: 1.5rem;
        max-width: 720px; margin-left: auto; margin-right: auto;
    }
    @media (max-width: 720px) {
        .bf-row { grid-template-columns: 1fr; }
        .bf-label { text-align: left; padding-top: 0; }
    }
</style>

<div class="ps-breadcrumb">Attributes &amp; Features &gt; Features</div>

<div style="margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">
        {{ $mode === 'edit' ? 'Editing feature '.$feature->name : 'Add new feature' }}
    </h1>
</div>

<div class="af-tabs">
    <a href="{{ route('admin.catalog.attributes') }}">Attributes</a>
    <a href="{{ route('admin.catalog.features') }}" class="active">Features</a>
</div>

<form method="post"
      action="{{ $mode === 'edit' ? route('admin.catalog.features.update', $feature) : route('admin.catalog.features.store') }}">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif

    <div class="card bf-wrap">
        <div class="card-head"><h3 style="margin:0;">Feature</h3></div>

        <div class="bf-row">
            <div class="bf-label">Name <span class="req">*</span></div>
            <div>
                <input name="name" value="{{ old('name', $feature->name) }}" required maxlength="100">
                <div class="bf-hint">e.g. Composition, Height, Material</div>
            </div>
        </div>

        <div class="bf-row">
            <div class="bf-label">Position</div>
            <div>
                <input type="number" name="position" min="0" value="{{ old('position', $feature->position ?? 0) }}" style="max-width:8rem;">
            </div>
        </div>
    </div>

    <div class="bf-actions">
        <a href="{{ $mode === 'edit' ? route('admin.catalog.features.show', $feature) : route('admin.catalog.features') }}" class="btn btn-ghost">Cancel</a>
        <button class="btn btn-primary" type="submit">Save</button>
    </div>
</form>
@endsection
