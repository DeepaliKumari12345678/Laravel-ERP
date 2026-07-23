@extends('admin.layouts.app')

@section('title', 'Settings')

@section('content')
<style>
    .settings-shell { display:grid; grid-template-columns:230px minmax(0,920px); gap:1.25rem; align-items:start; }
    .settings-nav { padding:.55rem; position:sticky; top:1rem; }
    .settings-nav a {
        display:flex; align-items:center; justify-content:space-between; gap:.75rem;
        padding:.72rem .8rem; border-radius:5px; color:var(--ps-ink); text-decoration:none;
        font-size:.88rem; font-weight:600;
    }
    .settings-nav a:hover { background:#f3f6f7; }
    .settings-nav a.active { background:#e8f8fb; color:#168ca4; }
    .settings-nav a.active::after { content:'›'; font-size:1.2rem; }
    .settings-card { padding:0; overflow:hidden; }
    .settings-head { padding:1.3rem 1.5rem; border-bottom:1px solid var(--ps-line); background:#fbfcfc; }
    .settings-head h2 { margin:0 0 .3rem; font-size:1.18rem; }
    .settings-head p { margin:0; color:var(--ps-muted); font-size:.86rem; }
    .settings-section { padding:1.25rem 1.5rem; border-bottom:1px solid var(--ps-line); }
    .settings-section:last-of-type { border-bottom:0; }
    .settings-section-title { margin:0 0 1rem; font-size:.94rem; color:var(--ps-ink); }
    .settings-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem 1.15rem; }
    .settings-field { display:flex; flex-direction:column; gap:.35rem; color:var(--ps-ink); font-size:.82rem; font-weight:600; }
    .settings-field.full { grid-column:1 / -1; }
    .settings-field input, .settings-field select, .settings-field textarea { margin:0; }
    .settings-hint { color:var(--ps-muted); font-size:.75rem; font-weight:400; line-height:1.4; }
    .settings-error { color:var(--danger); font-size:.75rem; font-weight:500; }
    .settings-toggle {
        flex-direction:row; align-items:flex-start; gap:.7rem; min-height:58px;
        border:1px solid var(--ps-line); border-radius:5px; padding:.8rem; background:#fbfcfc;
    }
    .settings-toggle input { width:auto; margin-top:.12rem; }
    .settings-actions {
        display:flex; align-items:center; justify-content:space-between; gap:1rem;
        padding:1rem 1.5rem; background:#fbfcfc; border-top:1px solid var(--ps-line);
    }
    .settings-global-note { color:var(--ps-muted); font-size:.77rem; }
    .shop-logo-field { display:flex; flex-direction:column; gap:.65rem; }
    .shop-logo-preview {
        width:72px; height:72px; border-radius:50%; object-fit:cover;
        border:2px solid var(--ps-line); background:#f3f6f7;
        display:grid; place-items:center; color:var(--ps-muted); font-size:.72rem; font-weight:700;
    }
    .shop-logo-preview.placeholder { font-size:.7rem; text-align:center; padding:.35rem; }
    @media (max-width:900px) {
        .settings-shell { grid-template-columns:1fr; }
        .settings-nav { position:static; display:flex; flex-wrap:wrap; gap:.25rem; }
        .settings-nav a { width:auto; }
    }
    @media (max-width:620px) {
        .settings-grid { grid-template-columns:1fr; }
        .settings-field.full { grid-column:auto; }
    }
</style>

@php
    $sections = collect($definition['fields'])->groupBy(
        fn (array $field) => $field['section'] ?? 'General settings',
        preserveKeys: true
    );
@endphp

<div class="ps-breadcrumb">Shop Parameters &gt; {{ $definition['label'] }}</div>
<h1 class="page-title">Shop Parameters</h1>
<p class="page-sub">Configure the defaults used throughout your ERP.</p>

<div class="settings-shell">
    <nav class="card settings-nav" aria-label="Settings groups">
        @foreach($groups as $key => $group)
            <a href="{{ route('admin.settings.group', $key) }}" class="{{ $activeGroup === $key ? 'active' : '' }}">
                <span>{{ $group['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <form method="post" action="{{ route('admin.settings.update', $activeGroup) }}" class="card settings-card" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <header class="settings-head">
            <h2>{{ $definition['label'] }}</h2>
            <p>{{ $definition['description'] }}</p>
        </header>

        @foreach($sections as $sectionLabel => $sectionFields)
            <section class="settings-section">
                <h3 class="settings-section-title">{{ $sectionLabel }}</h3>
                <div class="settings-grid">
                    @foreach($sectionFields as $key => $field)
                        @php
                            $type = $field['type'] ?? 'text';
                            $currentValue = old($key, $values[$key]);
                        @endphp

                        @if($type === 'boolean')
                            <label class="settings-field settings-toggle">
                                <input type="checkbox" name="{{ $key }}" value="1"
                                    @checked($currentValue == '1' || $currentValue === true)>
                                <span>
                                    {{ $field['label'] }}
                                    @if(!empty($field['hint']))
                                        <span class="settings-hint" style="display:block;margin-top:.2rem;">{{ $field['hint'] }}</span>
                                    @endif
                                </span>
                            </label>
                        @elseif($type === 'static')
                            <div class="settings-field">
                                <span>{{ $field['label'] }}</span>
                                <div style="padding:.65rem .75rem;border:1px solid var(--ps-line);border-radius:4px;background:#f7f9fa;color:var(--ps-ink);font-weight:600;">
                                    {{ $field['value'] ?? $currentValue }}
                                </div>
                                @if(!empty($field['hint']))<span class="settings-hint">{{ $field['hint'] }}</span>@endif
                            </div>
                        @elseif($type === 'image')
                            @php
                                $logoUrl = is_string($currentValue) && $currentValue !== ''
                                    ? asset('storage/'.$currentValue)
                                    : null;
                            @endphp
                            <div class="settings-field full">
                                <span>{{ $field['label'] }}</span>
                                <div class="shop-logo-field">
                                    @if($logoUrl)
                                        <img class="shop-logo-preview" src="{{ $logoUrl }}" alt="{{ $field['label'] }}">
                                    @else
                                        <div class="shop-logo-preview placeholder">No logo</div>
                                    @endif
                                    <input type="file" name="{{ $key }}" accept=".jpg,.jpeg,.png,.webp,.gif,image/*">
                                    @if($logoUrl)
                                        <label style="display:flex;align-items:center;gap:.45rem;font-weight:500;">
                                            <input type="checkbox" name="remove_{{ $key }}" value="1" style="width:auto;"> Remove current logo
                                        </label>
                                    @endif
                                    @if(!empty($field['hint']))<span class="settings-hint">{{ $field['hint'] }}</span>@endif
                                    @error($key)<span class="settings-error">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        @elseif($type === 'textarea')
                            <label class="settings-field full">
                                <span>{{ $field['label'] }}</span>
                                <textarea name="{{ $key }}" rows="3">{{ $currentValue }}</textarea>
                                @if(!empty($field['hint']))<span class="settings-hint">{{ $field['hint'] }}</span>@endif
                                @error($key)<span class="settings-error">{{ $message }}</span>@enderror
                            </label>
                        @elseif($type === 'country')
                            <label class="settings-field">
                                <span>{{ $field['label'] }}</span>
                                <select
                                    id="{{ $key }}"
                                    name="{{ $key }}"
                                    data-country-select
                                    data-state-target="PS_SHOP_STATE"
                                    data-states-url="{{ route('admin.locations.states') }}"
                                >
                                    @include('admin.partials.country-options', ['selectedCountry' => $currentValue])
                                </select>
                                @error($key)<span class="settings-error">{{ $message }}</span>@enderror
                            </label>
                        @elseif($type === 'state')
                            <label class="settings-field">
                                <span>{{ $field['label'] }}</span>
                                <select id="{{ $key }}" name="{{ $key }}" data-selected-state="{{ $currentValue }}">
                                    <option value="{{ $currentValue }}">{{ $currentValue ?: '— Select country first —' }}</option>
                                </select>
                                @error($key)<span class="settings-error">{{ $message }}</span>@enderror
                            </label>
                        @elseif($type === 'select')
                            <label class="settings-field">
                                <span>{{ $field['label'] }}</span>
                                <select name="{{ $key }}">
                                    @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}" @selected((string) $currentValue === (string) $optionValue)>{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                                @if(!empty($field['hint']))<span class="settings-hint">{{ $field['hint'] }}</span>@endif
                                @error($key)<span class="settings-error">{{ $message }}</span>@enderror
                            </label>
                        @else
                            <label class="settings-field">
                                <span>{{ $field['label'] }}</span>
                                <input
                                    type="{{ $type === 'number' ? 'number' : ($type === 'email' ? 'email' : 'text') }}"
                                    name="{{ $key }}"
                                    value="{{ $currentValue }}"
                                    @if($type === 'number') step="any" @endif
                                >
                                @if(!empty($field['hint']))<span class="settings-hint">{{ $field['hint'] }}</span>@endif
                                @error($key)<span class="settings-error">{{ $message }}</span>@enderror
                            </label>
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach

        <footer class="settings-actions">
            <span class="settings-global-note">These values are global across the shop.</span>
            <button class="btn btn-primary" type="submit">Save changes</button>
        </footer>
    </form>
</div>
@endsection
