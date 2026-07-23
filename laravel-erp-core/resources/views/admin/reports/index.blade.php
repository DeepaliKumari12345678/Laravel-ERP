@extends('admin.layouts.app')

@section('title', 'Stats')

@section('content')
@php
    $reportLabel = collect($menu)->firstWhere('key', $report)['label'] ?? 'Stats Dashboard';
    $presets = [
        'day' => 'Day',
        'month' => 'Month',
        'year' => 'Year',
        'day-1' => 'Day-1',
        'month-1' => 'Month-1',
        'year-1' => 'Year-1',
    ];
@endphp

<style>
    .st-layout { display:grid; grid-template-columns: 240px minmax(0,1fr); gap:1rem; align-items:start; }
    .st-nav {
        background:#fff; border:1px solid var(--ps-line); border-radius:4px; overflow:hidden;
    }
    .st-nav a {
        display:block; padding:0.7rem 0.9rem; color:var(--ps-ink); text-decoration:none;
        border-bottom:1px solid #f0f2f4; font-size:0.9rem;
    }
    .st-nav a:last-child { border-bottom:0; }
    .st-nav a:hover { background:#f5f7f8; }
    .st-nav a.active { background:#363a41; color:#fff; font-weight:600; }
    .st-toolbar {
        display:flex; flex-wrap:wrap; gap:0.45rem; align-items:end; margin-bottom:0.85rem;
        padding:0.75rem; background:#fff; border:1px solid var(--ps-line); border-radius:4px;
    }
    .st-toolbar .preset-btns { display:flex; flex-wrap:wrap; gap:0.3rem; }
    .st-toolbar .preset-btns a, .st-toolbar .preset-btns button {
        border:1px solid var(--ps-line); background:#fff; color:var(--ps-ink);
        padding:0.4rem 0.65rem; border-radius:3px; text-decoration:none; font:inherit; cursor:pointer;
    }
    .st-toolbar .preset-btns a.active { background:#363a41; color:#fff; border-color:#363a41; }
    .st-toolbar label { margin:0; min-width:130px; }
    .st-info {
        background:#e8f7fb; border:1px solid #b9e4ef; color:#1e6475;
        padding:0.7rem 0.85rem; border-radius:4px; margin-bottom:0.85rem; font-size:0.86rem;
    }
    .st-timeframe { display:flex; justify-content:flex-end; margin-bottom:0.75rem; }
    .st-timeframe select { width:auto; min-width:8rem; }
    .st-summary td { font-weight:600; background:#fafbfc; }
    .st-funnel {
        display:flex; flex-wrap:wrap; gap:0.55rem; align-items:center; margin-top:0.75rem;
    }
    .st-funnel-step {
        background:#fff; border:1px solid var(--ps-line); border-radius:4px;
        padding:0.65rem 0.85rem; min-width:7.5rem; text-align:center;
    }
    .st-funnel-step .num { font-size:1.15rem; font-weight:700; color:var(--ps-ink); }
    .st-funnel-step .lbl { color:var(--ps-muted); font-size:0.78rem; margin-top:0.15rem; }
    .st-funnel-arrow { color:var(--ps-muted); font-weight:700; }
    .st-funnel-pct {
        font-size:0.75rem; color:#1e6475; background:#e8f7fb; border-radius:10px;
        padding:0.15rem 0.45rem; margin-top:0.35rem; display:inline-block;
    }
    @media (max-width:980px) {
        .st-layout { grid-template-columns:1fr; }
    }
</style>

<div class="ps-breadcrumb">Sell &gt; Stats</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">Stats</h1>
</div>

<div class="st-layout">
    <nav class="st-nav">
        @foreach($menu as $item)
            <a href="{{ route('admin.reports.index', array_filter(['report' => $item['key'], 'from' => $from, 'to' => $to, 'timeframe' => $timeframe])) }}"
               class="{{ $report === $item['key'] ? 'active' : '' }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div>
        <form method="get" action="{{ route('admin.reports.index') }}" class="st-toolbar">
            <input type="hidden" name="report" value="{{ $report }}">
            <div class="preset-btns">
                @foreach($presets as $key => $label)
                    <a href="{{ route('admin.reports.index', ['report' => $report, 'preset' => $key, 'timeframe' => $timeframe]) }}"
                       class="{{ request('preset') === $key ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
            <label>From<input type="date" name="from" value="{{ $from }}"></label>
            <label>To<input type="date" name="to" value="{{ $to }}"></label>
            @if($report === 'dashboard')
                <input type="hidden" name="timeframe" value="{{ $timeframe }}">
            @endif
            <button class="btn btn-primary" type="submit">Save</button>
        </form>

        <div class="card">
            <div class="card-head"><h3 style="margin:0;">{{ $reportLabel }}</h3></div>

            @if($report === 'dashboard')
                <div class="st-info">The listed amounts do not include tax.</div>

                <form method="get" action="{{ route('admin.reports.index') }}" class="st-timeframe">
                    <input type="hidden" name="report" value="dashboard">
                    <input type="hidden" name="from" value="{{ $from }}">
                    <input type="hidden" name="to" value="{{ $to }}">
                    <label style="display:flex;align-items:center;gap:0.45rem;margin:0;">
                        Time frame
                        <select name="timeframe" onchange="this.form.submit()">
                            <option value="daily" @selected($timeframe === 'daily')>Daily</option>
                            <option value="monthly" @selected($timeframe === 'monthly')>Monthly</option>
                            <option value="yearly" @selected($timeframe === 'yearly')>Yearly</option>
                        </select>
                    </label>
                </form>

                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                        <tr>
                            <th></th>
                            <th>Visits</th>
                            <th>Registrations</th>
                            <th>Placed orders</th>
                            <th>Bought items</th>
                            <th>% of registrations</th>
                            <th>% of orders</th>
                            <th>Revenue</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($data['series'] as $row)
                            <tr>
                                <td><strong>{{ $row['label'] }}</strong></td>
                                <td>{{ number_format($row['visits']) }}</td>
                                <td>{{ number_format($row['registrations']) }}</td>
                                <td>{{ number_format($row['orders']) }}</td>
                                <td>{{ number_format($row['items']) }}</td>
                                <td>{{ number_format($row['pct_registrations'], 2) }}%</td>
                                <td>{{ number_format($row['pct_orders'], 2) }}%</td>
                                <td>{{ number_format($row['revenue'], 2) }} {{ $currency }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" style="color:var(--ps-muted);">No data for this period.</td></tr>
                        @endforelse
                        </tbody>
                        <tfoot class="st-summary">
                        <tr>
                            <td>Total</td>
                            <td>{{ number_format($data['totals']['visits']) }}</td>
                            <td>{{ number_format($data['totals']['registrations']) }}</td>
                            <td>{{ number_format($data['totals']['orders']) }}</td>
                            <td>{{ number_format($data['totals']['items']) }}</td>
                            <td>{{ number_format($data['totals']['pct_registrations'], 2) }}%</td>
                            <td>{{ number_format($data['totals']['pct_orders'], 2) }}%</td>
                            <td>{{ number_format($data['totals']['revenue'], 2) }} {{ $currency }}</td>
                        </tr>
                        <tr>
                            <td>Average</td>
                            <td>{{ number_format($data['average']['visits'], 2) }}</td>
                            <td>{{ number_format($data['average']['registrations'], 2) }}</td>
                            <td>{{ number_format($data['average']['orders'], 2) }}</td>
                            <td>{{ number_format($data['average']['items'], 2) }}</td>
                            <td>{{ number_format($data['average']['pct_registrations'], 2) }}%</td>
                            <td>{{ number_format($data['average']['pct_orders'], 2) }}%</td>
                            <td>{{ number_format($data['average']['revenue'], 2) }} {{ $currency }}</td>
                        </tr>
                        <tr>
                            <td>Forecast</td>
                            <td>{{ number_format($data['forecast']['visits'], 2) }}</td>
                            <td>{{ number_format($data['forecast']['registrations'], 2) }}</td>
                            <td>{{ number_format($data['forecast']['orders'], 2) }}</td>
                            <td>{{ number_format($data['forecast']['items'], 2) }}</td>
                            <td>{{ number_format($data['forecast']['pct_registrations'], 2) }}%</td>
                            <td>{{ number_format($data['forecast']['pct_orders'], 2) }}%</td>
                            <td>{{ number_format($data['forecast']['revenue'], 2) }} {{ $currency }}</td>
                        </tr>
                        </tfoot>
                    </table>
                </div>

                <div style="margin-top:1.25rem;">
                    <h3 style="margin:0 0 0.65rem;">Conversion</h3>
                    <div class="st-funnel">
                        <div class="st-funnel-step">
                            <div class="num">{{ number_format($data['conversion']['visitors']) }}</div>
                            <div class="lbl">Visitors</div>
                        </div>
                        <span class="st-funnel-arrow">→</span>
                        <div class="st-funnel-step">
                            <div class="num">{{ number_format($data['conversion']['accounts']) }}</div>
                            <div class="lbl">Accounts</div>
                            <div class="st-funnel-pct">{{ number_format($data['conversion']['pct_accounts'], 2) }}%</div>
                        </div>
                        <span class="st-funnel-arrow">→</span>
                        <div class="st-funnel-step">
                            <div class="num">{{ number_format($data['conversion']['carts']) }}</div>
                            <div class="lbl">Carts</div>
                            <div class="st-funnel-pct">{{ number_format($data['conversion']['pct_carts'], 2) }}%</div>
                        </div>
                        <span class="st-funnel-arrow">→</span>
                        <div class="st-funnel-step">
                            <div class="num">{{ number_format($data['conversion']['full_carts']) }}</div>
                            <div class="lbl">Full carts</div>
                            <div class="st-funnel-pct">{{ number_format($data['conversion']['pct_full'], 2) }}%</div>
                        </div>
                        <span class="st-funnel-arrow">→</span>
                        <div class="st-funnel-step">
                            <div class="num">{{ number_format($data['conversion']['orders']) }}</div>
                            <div class="lbl">Orders</div>
                            <div class="st-funnel-pct">{{ number_format($data['conversion']['pct_orders'], 2) }}%</div>
                        </div>
                    </div>
                    <p class="team-muted" style="margin-top:0.75rem;">
                        Overall conversion: <strong>{{ number_format($data['conversion']['overall'], 2) }}%</strong>
                    </p>
                </div>

            @elseif($report === 'available-quantities')
                <div class="st-info">
                    Total units in stock: <strong>{{ number_format($data['total_units'], 0) }}</strong>
                    · Low stock products: <strong>{{ $data['low'] }}</strong>
                </div>
                <div style="overflow-x:auto;">
                    <table>
                        <thead><tr><th>ID</th><th>Product</th><th>SKU</th><th>Category</th><th>Qty</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse($data['products'] as $product)
                            <tr>
                                <td>{{ $product->id }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->sku ?: '—' }}</td>
                                <td>{{ $product->category?->name ?? '—' }}</td>
                                <td><strong>{{ number_format((float) $product->quantity, 0) }}</strong></td>
                                <td>{{ $product->active ? 'Enabled' : 'Disabled' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="color:var(--ps-muted);">No tracked products.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

            @elseif(in_array($report, ['best-brands', 'best-categories', 'best-products'], true))
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                        <tr>
                            <th>{{ $report === 'best-products' ? 'Product' : 'Name' }}</th>
                            @if($report === 'best-products')<th>SKU</th>@endif
                            <th>Quantity</th>
                            <th>Revenue</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($data['rows'] as $row)
                            <tr>
                                <td>{{ $row->label ?? $row->name }}</td>
                                @if($report === 'best-products')<td>{{ $row->sku ?: '—' }}</td>@endif
                                <td>{{ number_format((float) $row->qty, 0) }}</td>
                                <td>{{ number_format((float) $row->revenue, 2) }} {{ $currency }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="color:var(--ps-muted);">No sales in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

            @elseif($report === 'best-customers')
                <div style="overflow-x:auto;">
                    <table>
                        <thead><tr><th>Customer</th><th>Orders</th><th>Revenue</th></tr></thead>
                        <tbody>
                        @forelse($data['rows'] as $row)
                            <tr>
                                <td>{{ $row->customer?->full_name ?? ('#'.$row->customer_id) }}</td>
                                <td>{{ $row->orders_count }}</td>
                                <td>{{ number_format((float) $row->revenue, 2) }} {{ $currency }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" style="color:var(--ps-muted);">No customers in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

            @elseif($report === 'orders')
                <div style="overflow-x:auto;">
                    <table>
                        <thead><tr><th>Status</th><th>Orders</th><th>Revenue</th></tr></thead>
                        <tbody>
                        @forelse($data['rows'] as $row)
                            <tr>
                                <td>{{ $row->status }}</td>
                                <td>{{ $row->total }}</td>
                                <td>{{ number_format((float) $row->revenue, 2) }} {{ $currency }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" style="color:var(--ps-muted);">No orders in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
