@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Edit: '.$title->name : 'Add new title')

@section('content')
<style>
    .cg-tabs { display:flex; gap:0; border-bottom:1px solid var(--ps-line); margin-bottom:1.15rem; }
    .cg-tabs a {
        padding:0.7rem 1.1rem; text-decoration:none; color:var(--ps-muted); font-weight:600;
        border-bottom:2px solid transparent; margin-bottom:-1px;
    }
    .cg-tabs a.active { color:var(--ps-ink); border-bottom-color:#25b9d7; }
    .tf-wrap { max-width: 920px; margin: 0 auto; }
    .tf-row {
        display:grid; grid-template-columns: 200px minmax(0,1fr); gap:1.15rem; align-items:start;
        padding:1rem 0; border-bottom:1px solid #f0f2f4;
    }
    .tf-row:last-of-type { border-bottom:0; }
    .tf-label { font-weight:600; color:var(--ps-ink); padding-top:0.5rem; text-align:right; }
    .tf-label .req { color:var(--danger); }
    .tf-hint { color:var(--ps-muted); font-size:0.78rem; margin-top:0.35rem; }
    .tf-radios { display:grid; gap:0.55rem; padding-top:0.25rem; }
    .tf-radios label { display:flex; align-items:center; gap:0.45rem; margin:0; color:var(--ps-ink); }
    .tf-radios input { width:auto; }
    .tf-file {
        display:flex; align-items:center; max-width:420px;
        border:1px solid var(--ps-line); border-radius:3px; overflow:hidden; background:#fff;
    }
    .tf-file input[type="file"] { display:none; }
    .tf-file-name {
        flex:1; padding:0.55rem 0.75rem; color:var(--ps-muted); font-size:0.88rem;
        white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .tf-file-browse {
        border:0; border-left:1px solid var(--ps-line); background:#f4f6f7;
        padding:0.55rem 0.9rem; cursor:pointer; font:inherit;
    }
    .tf-preview { margin-top:0.75rem; }
    .tf-preview img {
        border:1px solid var(--ps-line); border-radius:3px; background:#fff; display:block;
    }
    .tf-actions {
        display:flex; justify-content:space-between; gap:1rem; margin-top:1.35rem;
        max-width:920px; margin-left:auto; margin-right:auto;
    }
    @media (max-width:720px) {
        .tf-row { grid-template-columns:1fr; }
        .tf-label { text-align:left; }
    }
</style>

<div class="ps-breadcrumb">Customer Settings &gt; Titles</div>

<div style="margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">
        {{ $mode === 'edit' ? 'Edit: '.$title->name : 'Add new title' }}
    </h1>
</div>

<div class="cg-tabs">
    <a href="{{ route('admin.settings.group', ['group' => 'customers']) }}">Customer Settings</a>
    <a href="{{ route('admin.customer-groups.index') }}">Groups</a>
    <a href="{{ route('admin.customer-titles.index') }}" class="active">Titles</a>
</div>

<form method="post" enctype="multipart/form-data"
      action="{{ $mode === 'edit' ? route('admin.customer-titles.update', $title) : route('admin.customer-titles.store') }}">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif

    <div class="card tf-wrap">
        <div class="card-head"><h3 style="margin:0;">Titles</h3></div>

        <div class="tf-row">
            <div class="tf-label">Title <span class="req">*</span></div>
            <div>
                <input name="name" value="{{ old('name', $title->name) }}" required maxlength="50">
            </div>
        </div>

        <div class="tf-row">
            <div class="tf-label">Gender</div>
            <div class="tf-radios">
                @php($gender = old('gender', $title->gender ?: 'male'))
                <label><input type="radio" name="gender" value="male" @checked($gender === 'male')> Male</label>
                <label><input type="radio" name="gender" value="female" @checked($gender === 'female')> Female</label>
                <label><input type="radio" name="gender" value="neutral" @checked($gender === 'neutral')> Neutral</label>
            </div>
        </div>

        <div class="tf-row">
            <div class="tf-label">Image</div>
            <div>
                <div class="tf-file">
                    <span class="tf-file-name" id="title-file-name">Choose file(s)</span>
                    <button type="button" class="tf-file-browse" id="title-browse">Browse</button>
                    <input type="file" name="image" id="title-image" accept="image/*">
                </div>
                @if($title->image_url)
                    <div class="tf-preview" id="title-preview">
                        <img src="{{ $title->image_url }}" alt=""
                             style="width:{{ max(16, (int) $title->image_width) }}px;height:{{ max(16, (int) $title->image_height) }}px;object-fit:contain;">
                        <label style="display:flex;align-items:center;gap:0.4rem;margin-top:0.5rem;">
                            <input type="checkbox" name="remove_image" value="1" style="width:auto;"> Delete image
                        </label>
                    </div>
                @endif
            </div>
        </div>

        <div class="tf-row">
            <div class="tf-label">Image width</div>
            <div>
                <input type="number" min="0" max="2000" name="image_width"
                       value="{{ old('image_width', $title->image_width ?? 16) }}" style="max-width:140px;">
                <div class="tf-hint">Image width in pixels. Enter "0" to use the original size.</div>
            </div>
        </div>

        <div class="tf-row">
            <div class="tf-label">Image height</div>
            <div>
                <input type="number" min="0" max="2000" name="image_height"
                       value="{{ old('image_height', $title->image_height ?? 16) }}" style="max-width:140px;">
                <div class="tf-hint">Image height in pixels. Enter "0" to use the original size.</div>
            </div>
        </div>
    </div>

    <div class="tf-actions">
        <a href="{{ route('admin.customer-titles.index') }}" class="btn btn-ghost">Cancel</a>
        <button class="btn btn-primary" type="submit">Save</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const input = document.getElementById('title-image');
    const browse = document.getElementById('title-browse');
    const name = document.getElementById('title-file-name');
    browse?.addEventListener('click', () => input?.click());
    input?.addEventListener('change', () => {
        if (name) name.textContent = input.files?.[0]?.name || 'Choose file(s)';
    });
})();
</script>
@endpush
