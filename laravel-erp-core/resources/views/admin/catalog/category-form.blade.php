@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Editing category '.$category->name : 'Add new category')

@section('content')
@php
    $meta = is_array($category->meta) ? $category->meta : [];
    $groupIds = old('group_ids', $meta['group_ids'] ?? $groups->pluck('id')->all());
    $groupIds = array_map('intval', (array) $groupIds);
    $redirectType = old('redirect_type', $meta['redirect_type'] ?? '301');
    $redirectCategoryId = old('redirect_category_id', $meta['redirect_category_id'] ?? null);
    $metaTitle = old('meta_title', $meta['meta_title'] ?? '');
    $metaDescription = old('meta_description', $meta['meta_description'] ?? '');
    $additional = old('additional_description', $meta['additional_description'] ?? '');
    $friendlyUrl = old('slug', $category->exists ? preg_replace('/-[a-z0-9]{4}$/', '', $category->slug) : '');
    $shopUrl = rtrim(config('app.url'), '/');
@endphp

<style>
    .cf-form-card { max-width: 980px; margin: 0 auto; }
    .cf-row {
        display: grid; grid-template-columns: 220px 1fr; gap: 1rem; align-items: start;
        padding: 1rem 0; border-bottom: 1px solid #f0f2f4;
    }
    .cf-label { font-weight: 600; color: var(--ps-ink); padding-top: 0.45rem; }
    .cf-label .req { color: var(--danger); }
    .cf-hint { color: var(--ps-muted); font-size: 0.78rem; margin-top: 0.35rem; }
    .cf-switch-row { display: flex; align-items: center; gap: 0.75rem; padding-top: 0.25rem; }
    .cf-switch {
        position: relative; width: 44px; height: 24px; border-radius: 12px; border: 0; cursor: pointer;
        background: #bbcdd2; transition: background .15s; flex-shrink: 0;
    }
    .cf-switch.on { background: #70b580; }
    .cf-switch::after {
        content: ''; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px;
        border-radius: 50%; background: #fff; transition: left .15s;
    }
    .cf-switch.on::after { left: 23px; }
    .cf-actions {
        display: flex; justify-content: space-between; gap: 1rem; margin-top: 1.25rem;
        padding-top: 1rem; border-top: 1px solid var(--ps-line);
    }
    .cf-preview {
        width: 120px; height: 120px; object-fit: cover; border: 1px solid var(--ps-line);
        border-radius: 4px; background: #f4f6f7; display: block; margin-bottom: 0.5rem;
    }
    .cf-seo-box {
        border: 1px solid var(--ps-line); border-radius: 4px; padding: 0.85rem 1rem; background: #fafbfc;
    }
    .cf-seo-box .url { color: #0d652d; font-size: 0.82rem; margin-bottom: 0.2rem; }
    .cf-seo-box .title { color: #1a0dab; font-size: 1.05rem; font-weight: 500; margin-bottom: 0.15rem; }
    .cf-seo-box .desc { color: #4d5156; font-size: 0.86rem; line-height: 1.4; }
    .cf-info {
        background: #e8f7fb; border: 1px solid #b9e4ef; border-radius: 4px;
        padding: 0.75rem 0.9rem; font-size: 0.84rem; color: #1e6475; margin-top: 0.65rem;
    }
    .cf-groups {
        max-height: 180px; overflow: auto; border: 1px solid var(--ps-line);
        border-radius: 4px; padding: 0.55rem 0.75rem; background: #fff;
    }
    .cf-groups label { display: flex; align-items: center; gap: 0.45rem; margin: 0.3rem 0; color: var(--ps-ink); }
    .cf-groups input { width: auto; }
    .cf-parents {
        max-height: 200px; overflow: auto; border: 1px solid var(--ps-line);
        border-radius: 4px; padding: 0.55rem 0.75rem; background: #fff;
    }
    .cf-parents label { display: flex; align-items: center; gap: 0.45rem; margin: 0.3rem 0; color: var(--ps-ink); }
    .cf-parents input { width: auto; }
    @media (max-width: 720px) {
        .cf-row { grid-template-columns: 1fr; }
    }
</style>

<div class="ps-breadcrumb">
    <a href="{{ route('admin.catalog.categories') }}">Catalog</a> &gt; Categories
</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">
        {{ $mode === 'edit' ? 'Editing category '.$category->name : 'Add new category' }}
    </h1>
</div>

<div class="card cf-form-card">
    <div class="card-head"><h3 style="margin:0;">Category</h3></div>

    <form method="post" enctype="multipart/form-data"
          action="{{ $mode === 'edit' ? route('admin.catalog.categories.update', $category) : route('admin.catalog.categories.store') }}">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <div class="cf-row">
            <div class="cf-label">Name <span class="req">*</span></div>
            <div>
                <input id="category-name" name="name" value="{{ old('name', $category->name) }}" required>
                <div class="cf-hint">Invalid characters: &lt;;&gt;;=#{}</div>
            </div>
        </div>

        <div class="cf-row">
            <div class="cf-label">Description</div>
            <div>
                <textarea id="category-description" name="description" rows="5">{{ old('description', $category->description) }}</textarea>
                <div class="cf-hint">Short description shown with the category.</div>
            </div>
        </div>

        <div class="cf-row">
            <div class="cf-label">Additional description</div>
            <div>
                <textarea name="additional_description" rows="4">{{ $additional }}</textarea>
                <div class="cf-hint">Text usually displayed after the product list on the category page. Good for longer SEO content.</div>
            </div>
        </div>

        <div class="cf-row">
            <div class="cf-label">Enabled</div>
            <div>
                <div class="cf-switch-row">
                    <button type="button" class="cf-switch {{ old('active', $category->active) ? 'on' : '' }}" data-switch="active"></button>
                    <span class="switch-text">{{ old('active', $category->active) ? 'Yes' : 'No' }}</span>
                    <input type="hidden" name="active" value="{{ old('active', $category->active) ? '1' : '0' }}">
                </div>
            </div>
        </div>

        <div class="cf-row">
            <div class="cf-label">Category cover image</div>
            <div>
                @if($category->cover_image_url)
                    <img class="cf-preview" src="{{ $category->cover_image_url }}" alt="Cover">
                    <label style="display:flex;align-items:center;gap:.45rem;margin-bottom:.55rem;">
                        <input type="checkbox" name="remove_cover_image" value="1" style="width:auto;"> Delete current cover
                    </label>
                @endif
                <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp,image/*">
                <div class="cf-hint">Usually displayed on the category page next to the description.</div>
            </div>
        </div>

        <div class="cf-row">
            <div class="cf-label">Category thumbnail</div>
            <div>
                @if($category->thumbnail_url)
                    <img class="cf-preview" src="{{ $category->thumbnail_url }}" alt="Thumbnail">
                    <label style="display:flex;align-items:center;gap:.45rem;margin-bottom:.55rem;">
                        <input type="checkbox" name="remove_thumbnail" value="1" style="width:auto;"> Delete current thumbnail
                    </label>
                @endif
                <input type="file" name="thumbnail" accept=".jpg,.jpeg,.png,.webp,image/*">
                <div class="cf-hint">Miniature used when displaying subcategories or menus.</div>
            </div>
        </div>

        <div class="cf-row">
            <div class="cf-label">SEO preview</div>
            <div>
                <div class="cf-seo-box">
                    <div class="url" id="seo-url">{{ $shopUrl }}/{{ $friendlyUrl ?: 'category' }}</div>
                    <div class="title" id="seo-title">{{ $metaTitle ?: ($category->name ?: 'Category name') }}</div>
                    <div class="desc" id="seo-desc">{{ $metaDescription ?: \Illuminate\Support\Str::limit(old('description', $category->description), 155) }}</div>
                </div>
                <div class="cf-hint">Preview of how this page may appear in search results.</div>
            </div>
        </div>

        <div class="cf-row">
            <div class="cf-label">Meta title</div>
            <div>
                <input id="meta-title" name="meta_title" value="{{ $metaTitle }}" maxlength="70" placeholder="To have a different title from the category name, enter it here.">
                <div class="cf-hint"><span id="meta-title-count">{{ strlen((string) $metaTitle) }}</span> of 70 characters used (recommended).</div>
            </div>
        </div>

        <div class="cf-row">
            <div class="cf-label">Meta description</div>
            <div>
                <textarea id="meta-description" name="meta_description" rows="3" maxlength="160" placeholder="Write a different description for search results.">{{ $metaDescription }}</textarea>
                <div class="cf-hint"><span id="meta-desc-count">{{ strlen((string) $metaDescription) }}</span> of 160 characters used (recommended).</div>
            </div>
        </div>

        <div class="cf-row">
            <div class="cf-label">Friendly URL <span class="req">*</span></div>
            <div>
                <input id="friendly-url" name="slug" value="{{ $friendlyUrl }}" required pattern="[a-z0-9]+(?:-[a-z0-9]+)*">
                <div class="cf-hint">Only letters, numbers, and hyphens. Accented characters are converted automatically.</div>
            </div>
        </div>

        <div class="cf-row">
            <div class="cf-label">Position</div>
            <div>
                <input type="number" min="0" name="position" value="{{ old('position', $category->position ?? 0) }}">
            </div>
        </div>

        <div class="cf-row">
            <div class="cf-label">Redirection when not displayed</div>
            <div>
                <select name="redirect_type" id="redirect-type">
                    <option value="301" @selected($redirectType === '301')>Permanent redirection to a category (301)</option>
                    <option value="302" @selected($redirectType === '302')>Temporary redirection to a category (302)</option>
                    <option value="404" @selected($redirectType === '404')>No redirection (404)</option>
                    <option value="410" @selected($redirectType === '410')>No redirection (410)</option>
                </select>
                <div id="redirect-target" style="margin-top:0.65rem;">
                    <select name="redirect_category_id">
                        <option value="">— Closest active parent (default) —</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((string) $redirectCategoryId === (string) $parent->id)>{{ $parent->name }}</option>
                        @endforeach
                    </select>
                    <div class="cf-hint">By default, the closest active parent category will be used if none is selected.</div>
                </div>
                <div class="cf-info">
                    <strong>301</strong> permanently display another category ·
                    <strong>302</strong> temporarily display another category ·
                    <strong>404</strong> Not Found ·
                    <strong>410</strong> Gone
                </div>
            </div>
        </div>

        <div class="cf-row">
            <div class="cf-label">Group access <span class="req">*</span></div>
            <div>
                <div class="cf-groups">
                    <label><input type="checkbox" id="groups-select-all" style="width:auto;"> Select all</label>
                    @forelse($groups as $group)
                        <label>
                            <input type="checkbox" name="group_ids[]" value="{{ $group->id }}" class="group-check" style="width:auto;"
                                @checked(in_array((int) $group->id, $groupIds, true))>
                            {{ $group->name }}
                        </label>
                    @empty
                        <div class="cf-hint">No customer groups found.</div>
                    @endforelse
                </div>
                <div class="cf-hint">Select the customer groups which will have access to this category.</div>
                <div class="cf-info">Visitor / Guest / Customer style groups control who can browse this category in a storefront context.</div>
            </div>
        </div>

        <div class="cf-row">
            <div class="cf-label">Parent category</div>
            <div>
                <div class="cf-parents">
                    <label>
                        <input type="radio" name="parent_id" value="" @checked(! old('parent_id', $category->parent_id))>
                        — Root (no parent) —
                    </label>
                    @foreach($parents as $parent)
                        <label>
                            <input type="radio" name="parent_id" value="{{ $parent->id }}"
                                @checked((string) old('parent_id', $category->parent_id) === (string) $parent->id)>
                            {{ $parent->name }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="cf-actions">
            <a href="{{ route('admin.catalog.categories') }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary" type="submit">Save</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-switch]').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = btn.parentElement.querySelector('input[name="active"]');
        const text = btn.parentElement.querySelector('.switch-text');
        const on = input.value !== '1';
        input.value = on ? '1' : '0';
        btn.classList.toggle('on', on);
        text.textContent = on ? 'Yes' : 'No';
    });
});

const selectAll = document.getElementById('groups-select-all');
const groupChecks = () => Array.from(document.querySelectorAll('.group-check'));
selectAll?.addEventListener('change', () => {
    groupChecks().forEach(el => { el.checked = selectAll.checked; });
});

function syncSeoPreview() {
    const name = document.getElementById('category-name')?.value || 'Category name';
    const slug = document.getElementById('friendly-url')?.value || 'category';
    const metaTitle = document.getElementById('meta-title')?.value;
    const metaDesc = document.getElementById('meta-description')?.value;
    const desc = document.getElementById('category-description')?.value || '';
    document.getElementById('seo-title').textContent = metaTitle || name;
    document.getElementById('seo-url').textContent = @json($shopUrl) + '/' + slug;
    document.getElementById('seo-desc').textContent = metaDesc || desc.slice(0, 155);
    document.getElementById('meta-title-count').textContent = String((metaTitle || '').length);
    document.getElementById('meta-desc-count').textContent = String((metaDesc || '').length);
}
['category-name', 'friendly-url', 'meta-title', 'meta-description', 'category-description'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', syncSeoPreview);
});

document.getElementById('category-name')?.addEventListener('input', (e) => {
    const slug = document.getElementById('friendly-url');
    if (!slug || slug.dataset.touched === '1') return;
    slug.value = e.target.value.toLowerCase().trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    syncSeoPreview();
});
document.getElementById('friendly-url')?.addEventListener('input', () => {
    document.getElementById('friendly-url').dataset.touched = '1';
});

function syncRedirectTarget() {
    const type = document.getElementById('redirect-type')?.value;
    const box = document.getElementById('redirect-target');
    if (box) box.style.display = (type === '301' || type === '302') ? 'block' : 'none';
}
document.getElementById('redirect-type')?.addEventListener('change', syncRedirectTarget);
syncRedirectTarget();
</script>
@endpush
