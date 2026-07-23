@extends('admin.layouts.app')

@section('title', 'Credit slips')

@section('content')
<div class="ps-breadcrumb">
    <a href="{{ route('admin.orders.index') }}">Orders</a> &gt; Credit slips
</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">Credit slips</h1>
</div>

{{-- Empty / list --}}
<div class="card" style="margin-bottom:1rem;">
    <div class="card-head">
        <h3 style="margin:0;">Credit slips ({{ $slips->total() }})</h3>
    </div>

    @if($slips->total() === 0)
        <div style="text-align:center;padding:2rem 1.5rem;">
            <h2 style="margin:0 0 0.5rem;font-size:1.25rem;">Manage your credit slips</h2>
            <p class="team-muted" style="max-width:36rem;margin:0 auto 1rem;line-height:1.5;">
                When a customer returns a product, a credit slip must be created in his favor.
                Generate vouchers and have a look at their follow-up.
            </p>
        </div>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($slips as $slip)
                    <tr>
                        <td>{{ $slip->number }}</td>
                        <td>{{ $slip->order?->reference ?? '—' }}</td>
                        <td>{{ $slip->customer?->full_name ?? '—' }}</td>
                        <td>{{ number_format((float) $slip->amount, 2) }} {{ $slip->currency }}</td>
                        <td>{{ $slip->reason ?: '—' }}</td>
                        <td>{{ ucfirst($slip->status) }}</td>
                        <td style="text-align:right;white-space:nowrap;">
                            <a class="btn btn-ghost" href="{{ route('admin.credit-slips.show', $slip) }}">View</a>
                            <a class="btn btn-primary" href="{{ route('admin.credit-slips.download', $slip) }}">Download</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $slips->links() }}
    @endif
</div>

{{-- By date PDF --}}
<div class="card" style="margin-bottom:1rem;">
    <div class="card-head"><h3 style="margin:0;">By date</h3></div>
    <form method="post" action="{{ route('admin.credit-slips.pdf-by-date') }}">
        @csrf
        <div class="team-form-row">
            <div class="team-form-label">From *</div>
            <div>
                <input type="date" name="from" value="{{ old('from', now()->toDateString()) }}" required>
                <div class="team-muted">Format: YYYY-MM-DD (inclusive).</div>
            </div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">To *</div>
            <div>
                <input type="date" name="to" value="{{ old('to', now()->toDateString()) }}" required>
                <div class="team-muted">Format: YYYY-MM-DD (inclusive).</div>
            </div>
        </div>
        <div class="team-form-actions">
            <button class="btn btn-primary" type="submit">Generate PDF file by date</button>
        </div>
    </form>
</div>

{{-- Options --}}
<div class="card" style="margin-bottom:1rem;">
    <div class="card-head"><h3 style="margin:0;">Credit slip options</h3></div>
    <form method="post" action="{{ route('admin.credit-slips.options.update') }}">
        @csrf
        @method('PUT')
        <div class="team-form-row">
            <div class="team-form-label">Credit slip prefix</div>
            <div>
                <input name="prefix" value="{{ old('prefix', $options['prefix']) }}" required>
                <div class="team-muted">Prefix used for credit slips.</div>
            </div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">Next number</div>
            <div>
                <input type="number" min="0" name="next_number" value="{{ old('next_number', $options['next_number']) }}">
                <div class="team-muted">Set 0 to continue from the current sequence.</div>
            </div>
        </div>
        <div class="team-form-actions">
            <button class="btn btn-primary" type="submit">Save</button>
        </div>
    </form>
</div>

{{-- Create --}}
<div class="card">
    <div class="card-head"><h3 style="margin:0;">Create credit slip</h3></div>
    <form method="post" action="{{ route('admin.credit-slips.store') }}">
        @csrf
        <div class="team-form-row">
            <div class="team-form-label">Order *</div>
            <div>
                <select name="order_id" required>
                    <option value="">— Select order —</option>
                    @foreach($orders as $order)
                        <option value="{{ $order->id }}" @selected((string) old('order_id') === (string) $order->id)>
                            {{ $order->reference }} — {{ number_format((float) $order->total, 2) }} {{ $order->currency }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">Amount *</div>
            <div><input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required></div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">Reason</div>
            <div><input name="reason" value="{{ old('reason') }}" placeholder="Return / refund"></div>
        </div>
        <div class="team-form-actions">
            <button class="btn btn-primary" type="submit">Create credit slip</button>
        </div>
    </form>
</div>
@endsection
