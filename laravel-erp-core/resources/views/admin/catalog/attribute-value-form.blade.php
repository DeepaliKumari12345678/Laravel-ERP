@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Editing attribute value '.$value->name : 'Attribute value')

@section('content')
@php
    $allowGroupSelect = $allowGroupSelect ?? true;
    $groups = $groups ?? collect([$group]);
    $selectedGroupId = (int) old('attribute_group_id', $value->attribute_group_id ?: $group->id);
    $selectedGroup = $groups->firstWhere('id', $selectedGroupId) ?? $group;
@endphp

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
    .color-row { display:flex; align-items:center; gap:0.75rem; }
    .color-row input[type="color"] { width:42px; height:36px; padding:0; border:1px solid var(--ps-line); border-radius:3px; background:#fff; cursor:pointer; }
    .bf-actions {
        display: flex; justify-content: space-between; gap: 1rem; margin-top: 1.5rem; flex-wrap: wrap;
        max-width: 720px; margin-left: auto; margin-right: auto;
    }
    .bf-actions-right { display:flex; gap:0.5rem; flex-wrap:wrap; }
    @media (max-width: 720px) {
        .bf-row { grid-template-columns: 1fr; }
        .bf-label { text-align: left; padding-top: 0; }
    }
</style>

<div class="ps-breadcrumb">Attributes &amp; Features &gt; Attributes</div>

<div style="margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">
        {{ $mode === 'edit' ? 'Editing attribute value '.$value->name : 'Attribute value' }}
    </h1>
</div>

<div class="af-tabs">
    <a href="{{ route('admin.catalog.attributes') }}" class="active">Attributes</a>
    <a href="{{ route('admin.catalog.features') }}">Features</a>
</div>

@php
    $storeRoute = $mode === 'edit'
        ? route('admin.catalog.attributes.values.update', [$group, $value])
        : ($group->exists
            ? route('admin.catalog.attributes.values.store', $group)
            : route('admin.catalog.attribute-values.store'));
@endphp

<form method="post" action="{{ $storeRoute }}" id="attribute-value-form">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif
    <input type="hidden" name="save_and_add" id="save_and_add" value="0">

    <div class="card bf-wrap">
        <div class="card-head"><h3 style="margin:0;">Attribute value</h3></div>

        <div class="bf-row">
            <div class="bf-label">Attribute group <span class="req">*</span></div>
            <div>
                @if($allowGroupSelect)
                    <select name="attribute_group_id" id="attribute_group_id" required>
                        @foreach($groups as $option)
                            <option value="{{ $option->id }}"
                                    data-type="{{ $option->type }}"
                                    @selected($selectedGroupId === (int) $option->id)>
                                {{ $option->name }} (#{{ $option->id }})
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="attribute_group_id" value="{{ $group->id }}">
                    <strong>{{ $group->name }} (#{{ $group->id }})</strong>
                @endif
                <div class="bf-hint">Values belong to one attribute (e.g. Size or Color)</div>
            </div>
        </div>

        <div class="bf-row">
            <div class="bf-label">Name <span class="req">*</span></div>
            <div>
                <input name="name" value="{{ old('name', $value->name) }}" required maxlength="100">
                <div class="bf-hint">e.g. S, M, L or Red, Blue</div>
            </div>
        </div>

        <div class="bf-row" id="color-field" style="{{ ($selectedGroup->type ?? '') === 'color' ? '' : 'display:none;' }}">
            <div class="bf-label">Color</div>
            <div>
                <div class="color-row">
                    <input type="color" id="color-picker" value="{{ old('color', $value->color ?: '#000000') }}">
                    <input name="color" id="color-text" value="{{ old('color', $value->color) }}" maxlength="20" placeholder="#000000" style="max-width:10rem;">
                </div>
            </div>
        </div>

        <div class="bf-row">
            <div class="bf-label">Position</div>
            <div>
                <input type="number" name="position" min="0" value="{{ old('position', $value->position ?? 0) }}" style="max-width:8rem;">
            </div>
        </div>
    </div>

    <div class="bf-actions">
        <a href="{{ $group->exists ? route('admin.catalog.attributes.show', $group) : route('admin.catalog.attributes') }}" class="btn btn-ghost">Cancel</a>
        <div class="bf-actions-right">
            @if($mode === 'create')
                <button class="btn btn-ghost" type="submit" onclick="document.getElementById('save_and_add').value='1'">Save then add another</button>
            @endif
            <button class="btn btn-primary" type="submit" onclick="document.getElementById('save_and_add').value='0'">Save</button>
        </div>
    </div>
</form>

<script>
(() => {
    const groupSelect = document.getElementById('attribute_group_id');
    const colorField = document.getElementById('color-field');
    const syncColor = () => {
        if (!colorField || !groupSelect) return;
        const type = groupSelect.options[groupSelect.selectedIndex]?.dataset?.type;
        colorField.style.display = type === 'color' ? '' : 'none';
    };
    groupSelect?.addEventListener('change', syncColor);
    syncColor();

    const picker = document.getElementById('color-picker');
    const text = document.getElementById('color-text');
    picker?.addEventListener('input', () => { text.value = picker.value; });
    text?.addEventListener('input', () => {
        if (/^#[0-9A-Fa-f]{6}$/.test(text.value)) picker.value = text.value;
    });
})();
</script>
@endsection
