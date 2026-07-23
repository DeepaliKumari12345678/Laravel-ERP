@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="ps-breadcrumb">
        <a href="{{ route('admin.dashboard') }}">Welcome</a> &gt; Dashboard
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
        <h1 class="page-title" style="margin-bottom:0.75rem;">Dashboard</h1>
        <div class="actions">
            <a class="btn btn-ghost" href="{{ route('admin.reports.index') }}">Stats</a>
            <a class="btn btn-primary" href="{{ route('admin.orders.index') }}">View orders</a>
        </div>
    </div>

    <div class="kpi-row">
        <div class="kpi active">
            <div class="label">Sales</div>
            <div class="value">{{ number_format($stats['sales'], 2) }} {{ $stats['currency'] }}</div>
        </div>
        <div class="kpi">
            <div class="label">Orders</div>
            <div class="value">{{ $stats['orders'] }}</div>
        </div>
        <div class="kpi">
            <div class="label">Cart value</div>
            <div class="value">{{ number_format($stats['cart_value'], 2) }}</div>
        </div>
        <div class="kpi">
            <div class="label">Customers</div>
            <div class="value">{{ $stats['customers'] }}</div>
        </div>
        <div class="kpi">
            <div class="label">Conversion</div>
            <div class="value">{{ $stats['conversion'] }}%</div>
        </div>
        <div class="kpi">
            <div class="label">Net profit*</div>
            <div class="value">{{ number_format($stats['net_profit'], 2) }}</div>
        </div>
    </div>

    <div class="grid grid-3" style="margin-bottom:1rem;">
        <div class="card">
            <div class="card-head">
                <h3>Activity</h3>
            </div>
            <div class="stat-line"><span>New customers (30d)</span><strong>{{ $activity['new_customers'] }}</strong></div>
            <div class="stat-line"><span>Total customers</span><strong>{{ $activity['total_customers'] }}</strong></div>
            <div class="stat-line"><span>New orders (30d)</span><strong>{{ $activity['new_orders'] }}</strong></div>
            <div class="stat-line"><span>Pending orders</span><strong>{{ $activity['pending_orders'] }}</strong></div>
            <div class="stat-line"><span>Low stock products</span><strong>{{ $activity['low_stock'] }}</strong></div>
            <div class="stat-line"><span>Products</span><strong>{{ $stats['products'] }}</strong></div>
            <div class="stat-line"><span>Employees</span><strong>{{ $stats['employees'] }}</strong></div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3>Sales</h3>
                <span style="color:var(--ps-muted);font-size:0.8rem;">Last 30 days</span>
            </div>
            <div class="chart-box">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3>Orders by status</h3>
            </div>
            <div class="chart-box sm">
                <canvas id="statusChart"></canvas>
            </div>
            <div style="margin-top:0.75rem;">
                @forelse($statusBreakdown as $status => $total)
                    <div class="stat-line">
                        <span><span class="status-dot" style="background:{{ ['pending'=>'#fbbd3b','paid'=>'#25b9d7','completed'=>'#70b580','cancelled'=>'#f54c3e','processing'=>'#6f7c82','shipped'=>'#3f7cac'][$status] ?? '#6c868e' }};"></span>{{ ucfirst($status) }}</span>
                        <strong>{{ $total }}</strong>
                    </div>
                @empty
                    <p style="color:var(--ps-muted);margin:0;">No orders yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <div class="card-head">
                <h3>Orders forecast</h3>
                <span style="color:var(--ps-muted);font-size:0.8rem;">Last 14 days</span>
            </div>
            <div class="chart-box">
                <canvas id="ordersChart"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3>Recent orders</h3>
                <a href="{{ route('admin.orders.index') }}" style="color:var(--ps-blue);font-size:0.85rem;font-weight:600;">View all</a>
            </div>
            <table>
                <thead>
                <tr><th>Reference</th><th>Customer</th><th>Status</th><th>Total</th></tr>
                </thead>
                <tbody>
                @forelse($recentOrders as $order)
                    <tr>
                        <td>{{ $order->reference }}</td>
                        <td>{{ $order->customer?->full_name ?? '—' }}</td>
                        <td><span class="badge badge-off">{{ $order->status }}</span></td>
                        <td>{{ number_format((float) $order->total, 2) }} {{ $order->currency }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No orders yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            <p style="margin:0.85rem 0 0;color:var(--ps-muted);font-size:0.78rem;">* Net profit is an estimate (35% of sales) until accounting is connected.</p>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    const salesLabels = @json($salesChart['labels']);
    const salesValues = @json($salesChart['values']);
    const orderLabels = @json($ordersByDay['labels']);
    const orderValues = @json($ordersByDay['values']);
    const statusLabels = @json(array_keys($statusBreakdown));
    const statusValues = @json(array_values($statusBreakdown));

    const blue = '#25b9d7';
    const grid = '#eef1f3';

    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: {
            labels: salesLabels,
            datasets: [{
                label: 'Sales',
                data: salesValues,
                borderColor: blue,
                backgroundColor: 'rgba(37,185,215,0.12)',
                fill: true,
                tension: 0.35,
                pointRadius: 0,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 8, color: '#6c868e' } },
                y: { beginAtZero: true, grid: { color: grid }, ticks: { color: '#6c868e' } }
            }
        }
    });

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusLabels.length ? statusLabels : ['No data'],
            datasets: [{
                data: statusValues.length ? statusValues : [1],
                backgroundColor: statusValues.length
                    ? ['#fbbd3b', '#25b9d7', '#70b580', '#f54c3e', '#6f7c82', '#3f7cac']
                    : ['#dbe2e8'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '62%'
        }
    });

    new Chart(document.getElementById('ordersChart'), {
        type: 'bar',
        data: {
            labels: orderLabels,
            datasets: [{
                label: 'Orders',
                data: orderValues,
                backgroundColor: '#fbbd3b',
                borderRadius: 3,
                maxBarThickness: 28,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#6c868e' } },
                y: { beginAtZero: true, ticks: { stepSize: 1, color: '#6c868e' }, grid: { color: grid } }
            }
        }
    });
})();
</script>
@endpush
