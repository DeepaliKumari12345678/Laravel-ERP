@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Editing status '.$status->name : 'Add new order status')

@section('content')
<style>
    .os-form-card { max-width: 920px; }
    .os-form-row {
        display:grid; grid-template-columns: 210px 1fr; gap:1rem; align-items:start;
        padding:0.85rem 0; border-bottom:1px solid #f0f2f4;
    }
    .os-form-row:last-of-type { border-bottom:0; }
    .os-form-label { font-weight:600; color:var(--ps-ink); padding-top:0.55rem; }
    .os-form-label .req { color:var(--danger); }
    .os-hint { color:var(--ps-muted); font-size:0.78rem; margin-top:0.3rem; }
    .os-check { display:flex; align-items:center; gap:0.45rem; padding:0.3rem 0; color:var(--ps-ink); }
    .os-check input { width:auto; }
    .os-color-row { display:flex; align-items:center; gap:0.75rem; }
    .os-color-row input[type="color"] { width:52px; height:36px; padding:2px; cursor:pointer; }
    .os-preview {
        display:inline-flex; align-items:center; padding:0.3rem 0.75rem; border-radius:3px;
        color:#fff; font-size:0.8rem; font-weight:600;
    }
    .os-form-actions {
        display:flex; justify-content:space-between; gap:1rem; margin-top:1.25rem;
        padding-top:1rem; border-top:1px solid var(--ps-line);
    }
    @media (max-width:720px) {
        .os-form-row { grid-template-columns:1fr; gap:0.35rem; }
        .os-form-label { padding-top:0; }
    }
</style>

<div class="ps-breadcrumb">
    Shop Parameters &gt;
    <a href="{{ route('admin.order-statuses.index') }}">Order statuses</a> &gt;
    {{ $mode === 'edit' ? 'Edit' : 'Add' }}
</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">
        @if($mode === 'edit')
            Editing status {{ $status->name }}
        @else
            Add new order status
        @endif
    </h1>
</div>

<div class="card os-form-card">
    <div class="card-head">
        <h3 style="margin:0;display:flex;align-items:center;gap:0.45rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 16 14"/></svg>
            Order status
        </h3>
    </div>

    <form method="post" action="{{ $mode === 'edit' ? route('admin.order-statuses.update', $status) : route('admin.order-statuses.store') }}">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <div class="os-form-row">
            <div class="os-form-label">Status name <span class="req">*</span></div>
            <div>
                <input id="status-name" name="name" value="{{ old('name', $status->name) }}" placeholder="e.g. Awaiting payment" required>
                <div class="os-hint">Shown on orders, in status history, and in customer emails.</div>
                @error('name')<div class="os-hint" style="color:var(--danger);">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="os-form-row">
            <div class="os-form-label">Code</div>
            <div>
                <input name="code" value="{{ old('code', $status->code) }}" placeholder="Generated from the name if left blank">
                <div class="os-hint">Internal identifier (lowercase letters, numbers, - and _). Existing orders follow automatically when it changes.</div>
                @error('code')<div class="os-hint" style="color:var(--danger);">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="os-form-row">
            <div class="os-form-label">Color <span class="req">*</span></div>
            <div>
                <div class="os-color-row">
                    <input id="status-color" type="color" name="color" value="{{ old('color', $status->color ?: '#607D8B') }}">
                    <span id="status-preview" class="os-preview" style="background:{{ old('color', $status->color ?: '#607D8B') }};">
                        {{ old('name', $status->name ?: 'Status preview') }}
                    </span>
                </div>
                <div class="os-hint">Used for the status label on order lists and order pages.</div>
                @error('color')<div class="os-hint" style="color:var(--danger);">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="os-form-row">
            <div class="os-form-label">Position</div>
            <div>
                <input type="number" min="0" name="position" value="{{ old('position', $status->position ?? 0) }}" style="max-width:140px;">
                <div class="os-hint">Order in which statuses appear in dropdowns.</div>
            </div>
        </div>

        <div class="os-form-row">
            <div class="os-form-label">Customer notification</div>
            <div>
                <label class="os-check">
                    <input type="checkbox" name="send_email" value="1" @checked(old('send_email', $status->send_email))>
                    Send an email to the customer when an order gets this status
                </label>
            </div>
        </div>

        <div class="os-form-row">
            <div class="os-form-label">Workflow behavior</div>
            <div>
                <label class="os-check">
                    <input type="checkbox" name="is_paid" value="1" @checked(old('is_paid', $status->is_paid))>
                    Consider the order as paid
                    <span class="os-hint" style="margin:0;">— triggers automatic invoicing when enabled in Order settings</span>
                </label>
                <label class="os-check">
                    <input type="checkbox" name="is_shipped" value="1" @checked(old('is_shipped', $status->is_shipped))>
                    Consider the order as shipped
                </label>
                <label class="os-check">
                    <input type="checkbox" name="is_delivered" value="1" @checked(old('is_delivered', $status->is_delivered))>
                    Consider the order as delivered
                </label>
                <label class="os-check">
                    <input type="checkbox" name="is_cancelled" value="1" @checked(old('is_cancelled', $status->is_cancelled))>
                    Consider the order as cancelled
                    <span class="os-hint" style="margin:0;">— excluded from sales totals and reports</span>
                </label>
                <label class="os-check">
                    <input type="checkbox" name="counts_as_validated" value="1" @checked(old('counts_as_validated', $status->counts_as_validated))>
                    Count as a validated sale
                    <span class="os-hint" style="margin:0;">— included in conversion and customer KPIs</span>
                </label>
            </div>
        </div>

        <div class="os-form-row">
            <div class="os-form-label">Active</div>
            <div>
                <label class="os-check">
                    <input type="checkbox" name="active" value="1" @checked(old('active', $status->active))>
                    Available when updating orders
                </label>
                @if($mode === 'edit' && $status->orders_count > 0)
                    <div class="os-hint">This status is currently used by {{ $status->orders_count }} order{{ $status->orders_count === 1 ? '' : 's' }}.</div>
                @endif
            </div>
        </div>

        <div class="os-form-actions">
            <a href="{{ route('admin.order-statuses.index') }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary" type="submit">Save</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
const statusName = document.getElementById('status-name');
const statusColor = document.getElementById('status-color');
const statusPreview = document.getElementById('status-preview');
function syncStatusPreview() {
    statusPreview.style.background = statusColor.value;
    statusPreview.textContent = statusName.value.trim() || 'Status preview';
}
statusName?.addEventListener('input', syncStatusPreview);
statusColor?.addEventListener('input', syncStatusPreview);
</script>
@endpush
@endsection
