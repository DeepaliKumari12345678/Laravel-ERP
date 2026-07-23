@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Edit payment method' : 'Add payment method')

@section('content')
<style>
    .bf-wrap { max-width: 720px; margin: 0 auto; }
    .bf-row {
        display: grid; grid-template-columns: 180px minmax(0, 1fr); gap: 1.25rem; align-items: start;
        padding: 1.1rem 0; border-bottom: 1px solid #f0f2f4;
    }
    .bf-row:last-of-type { border-bottom: 0; }
    .bf-label { font-weight: 600; color: var(--ps-ink); padding-top: 0.55rem; text-align: right; }
    .bf-label .req { color: var(--danger); }
    .bf-hint { color: var(--ps-muted); font-size: 0.78rem; margin-top: 0.35rem; }
    .cu-switch-row { display:flex; align-items:center; gap:0.75rem; padding-top:0.25rem; }
    .cu-switch {
        position:relative; width:44px; height:24px; border-radius:12px; border:0; cursor:pointer;
        background:#bbcdd2; transition:background .15s; flex-shrink:0;
    }
    .cu-switch.on { background:#70b580; }
    .cu-switch::after {
        content:''; position:absolute; top:3px; left:3px; width:18px; height:18px;
        border-radius:50%; background:#fff; transition:left .15s;
    }
    .cu-switch.on::after { left:23px; }
    .bf-actions {
        display: flex; justify-content: space-between; gap: 1rem; margin-top: 1.5rem;
        max-width: 720px; margin-left: auto; margin-right: auto;
    }
    @media (max-width: 720px) {
        .bf-row { grid-template-columns: 1fr; }
        .bf-label { text-align: left; padding-top: 0; }
    }
</style>

<div class="ps-breadcrumb">
    <a href="{{ route('admin.payment.methods') }}">Payment methods</a> &gt;
    {{ $mode === 'edit' ? 'Edit' : 'Add' }}
</div>

<div style="margin-bottom:1rem;text-align:center;">
    <h1 class="page-title" style="margin:0;">
        {{ $mode === 'edit' ? 'Edit payment method' : 'Add payment method' }}
    </h1>
</div>

<form method="post"
      action="{{ $mode === 'edit' ? route('admin.payment.methods.update', $method) : route('admin.payment.methods.store') }}"
      id="payment-method-form">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif

    <div class="card bf-wrap">
        <div class="card-head"><h3 style="margin:0;">Payment method</h3></div>

        <div class="bf-row">
            <div class="bf-label">Name <span class="req">*</span></div>
            <div>
                <input name="name" value="{{ old('name', $method->name) }}" required maxlength="150" placeholder="Cash on delivery">
            </div>
        </div>

        <div class="bf-row">
            <div class="bf-label">Code @if($mode === 'edit')<span class="req">*</span>@endif</div>
            <div>
                <input name="code" value="{{ old('code', $method->code) }}" maxlength="50" placeholder="cod" @required($mode === 'edit') style="max-width:14rem;">
                <div class="bf-hint">{{ $mode === 'create' ? 'Leave empty to generate from the name.' : 'Unique internal code used in orders and reports.' }}</div>
            </div>
        </div>

        <div class="bf-row">
            <div class="bf-label">Description</div>
            <div>
                <textarea name="description" rows="3" maxlength="500" placeholder="Shown to staff when selecting a payment method">{{ old('description', $method->description) }}</textarea>
            </div>
        </div>

        <div class="bf-row">
            <div class="bf-label">Position</div>
            <div>
                <input type="number" name="position" min="0" value="{{ old('position', $method->position ?? 0) }}" style="max-width:8rem;">
                <div class="bf-hint">Lower numbers appear first in lists.</div>
            </div>
        </div>

        <div class="bf-row">
            <div class="bf-label">Enabled</div>
            <div>
                <div class="cu-switch-row">
                    <button type="button" class="cu-switch {{ old('active', $method->active) ? 'on' : '' }}" data-switch="active"></button>
                    <span class="switch-text">{{ old('active', $method->active) ? 'Yes' : 'No' }}</span>
                    <input type="hidden" name="active" value="{{ old('active', $method->active) ? '1' : '0' }}">
                </div>
            </div>
        </div>
    </div>

    <div class="bf-actions">
        <a href="{{ route('admin.payment.methods') }}" class="btn btn-ghost">Cancel</a>
        <button class="btn btn-primary" type="submit">Save</button>
    </div>
</form>

<script>
(() => {
    document.querySelectorAll('[data-switch]').forEach(btn => {
        btn.addEventListener('click', () => {
            const name = btn.dataset.switch;
            const input = btn.parentElement.querySelector(`input[name="${name}"]`);
            const text = btn.parentElement.querySelector('.switch-text');
            const on = input.value !== '1';
            input.value = on ? '1' : '0';
            btn.classList.toggle('on', on);
            text.textContent = on ? 'Yes' : 'No';
        });
    });
})();
</script>
@endsection
