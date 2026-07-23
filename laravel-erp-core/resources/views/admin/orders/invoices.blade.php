@extends('admin.layouts.app')
@section('title', 'Invoices')
@section('content')
    <div class="ps-breadcrumb">
        <a href="{{ route('admin.orders.index') }}">Orders</a> &gt; Invoices
    </div>
    <h1 class="page-title">Invoices</h1>

    <div class="card" style="margin-bottom:1rem;">
    <div class="card-head"><h3 style="margin:0;">By date</h3></div>

    <form method="post" action="{{ route('admin.invoices.pdf-by-date') }}">
        @csrf

        <div class="team-form-row">
            <div class="team-form-label">From</div>
            <div>
                <input type="date" name="from" value="{{ old('from', now()->toDateString()) }}" required>
                <div class="team-muted">Format: YYYY-MM-DD (inclusive).</div>
            </div>
        </div>

        <div class="team-form-row">
            <div class="team-form-label">To</div>
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
<div class="card" style="margin-bottom:1rem;">
    <div class="card-head"><h3 style="margin:0;">By date</h3></div>

    <form method="post" action="{{ route('admin.invoices.pdf-by-date') }}">
        @csrf

        <div class="team-form-row">
            <div class="team-form-label">From</div>
            <div>
                <input type="date" name="from" value="{{ old('from', now()->toDateString()) }}" required>
                <div class="team-muted">Format: YYYY-MM-DD (inclusive).</div>
            </div>
        </div>

        <div class="team-form-row">
            <div class="team-form-label">To</div>
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
<div class="card">
    <div class="card-head"><h3 style="margin:0;">Invoice options</h3></div>

    <form method="post" action="{{ route('admin.invoices.options.update') }}">
        @csrf
        @method('PUT')

        <div class="team-form-row">
            <div class="team-form-label">Enable invoices</div>
            <div>
                <label>
                    <input type="hidden" name="enabled" value="0">
                    <input type="checkbox" name="enabled" value="1" style="width:auto;" @checked($options['enabled'])>
                    Yes
                </label>
                <div class="team-muted">When enabled, customers can receive an invoice.</div>
            </div>
        </div>

        <div class="team-form-row">
            <div class="team-form-label">Enable tax breakdown</div>
            <div>
                <label>
                    <input type="hidden" name="tax_breakdown" value="0">
                    <input type="checkbox" name="tax_breakdown" value="1" style="width:auto;" @checked($options['tax_breakdown'])>
                    Yes
                </label>
            </div>
        </div>

        <div class="team-form-row">
            <div class="team-form-label">Enable product image</div>
            <div>
                <label>
                    <input type="hidden" name="product_image" value="0">
                    <input type="checkbox" name="product_image" value="1" style="width:auto;" @checked($options['product_image'])>
                    Yes
                </label>
            </div>
        </div>

        <div class="team-form-row">
            <div class="team-form-label">Invoice prefix</div>
            <div>
                <input name="prefix" value="{{ old('prefix', $options['prefix']) }}">
                <div class="team-muted">Example: #IN00001</div>
            </div>
        </div>

        <div class="team-form-row">
            <div class="team-form-label">Add current year to invoice number</div>
            <div>
                <label>
                    <input type="hidden" name="year_active" value="0">
                    <input type="checkbox" name="year_active" value="1" style="width:auto;" @checked($options['year_active'])>
                    Yes
                </label>
            </div>
        </div>

        <div class="team-form-row">
            <div class="team-form-label">Reset sequential number each year</div>
            <div>
                <label>
                    <input type="hidden" name="year_reset" value="0">
                    <input type="checkbox" name="year_reset" value="1" style="width:auto;" @checked($options['year_reset'])>
                    Yes
                </label>
            </div>
        </div>

        <div class="team-form-row">
            <div class="team-form-label">Position of the year</div>
            <div>
                <label style="display:block;margin:0.25rem 0;">
                    <input type="radio" name="year_position" value="after" style="width:auto;"
                        @checked($options['year_position'] === 'after')>
                    After the sequential number
                </label>
                <label style="display:block;margin:0.25rem 0;">
                    <input type="radio" name="year_position" value="before" style="width:auto;"
                        @checked($options['year_position'] === 'before')>
                    Before the sequential number
                </label>
            </div>
        </div>

        <div class="team-form-row">
            <div class="team-form-label">Invoice number</div>
            <div>
                <input type="number" min="0" name="next_number" value="{{ old('next_number', $options['next_number']) }}">
                <div class="team-muted">Set 0 to keep the current sequence.</div>
            </div>
        </div>

        <div class="team-form-row">
            <div class="team-form-label">Legal free text</div>
            <div>
                <textarea name="legal_text" rows="3">{{ old('legal_text', $options['legal_text']) }}</textarea>
            </div>
        </div>

        <div class="team-form-row">
            <div class="team-form-label">Footer text</div>
            <div>
                <textarea name="footer_text" rows="3">{{ old('footer_text', $options['footer_text']) }}</textarea>
            </div>
        </div>

        <div class="team-form-row">
            <div class="team-form-label">Invoice model</div>
            <div>
                <select name="model">
                    <option value="invoice" @selected($options['model'] === 'invoice')>invoice</option>
                </select>
            </div>
        </div>

        <div class="team-form-row">
            <div class="team-form-label">Use disk as PDF cache</div>
            <div>
                <label>
                    <input type="hidden" name="use_disk" value="0">
                    <input type="checkbox" name="use_disk" value="1" style="width:auto;" @checked($options['use_disk'])>
                    Yes
                </label>
            </div>
        </div>

        <div class="team-form-actions">
            <button class="btn btn-primary" type="submit">Save</button>
        </div>
    </form>
</div>
@endsection
