<?php

namespace App\Http\Controllers\Admin;

use App\Core\Configuration\Configuration;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StatsController extends Controller
{
    /**
     * @return list<array{key: string, label: string}>
     */
    protected function menu(): array
    {
        return [
            ['key' => 'available-quantities', 'label' => 'Available quantities'],
            ['key' => 'best-brands', 'label' => 'Best brands'],
            ['key' => 'best-categories', 'label' => 'Best categories'],
            ['key' => 'best-customers', 'label' => 'Best customers'],
            ['key' => 'best-products', 'label' => 'Best products'],
            ['key' => 'orders', 'label' => 'Orders'],
            ['key' => 'dashboard', 'label' => 'Stats Dashboard'],
        ];
    }

    public function index(Request $request): View
    {
        $report = $request->string('report')->toString() ?: 'dashboard';
        $allowed = collect($this->menu())->pluck('key')->all();
        if (! in_array($report, $allowed, true)) {
            $report = 'dashboard';
        }

        [$from, $to] = $this->resolveDateRange($request);
        $currency = Configuration::get('PS_CURRENCY_DEFAULT', 'INR');
        $timeframe = $request->string('timeframe')->toString() ?: 'daily';

        $data = match ($report) {
            'available-quantities' => $this->availableQuantities(),
            'best-brands' => $this->bestBrands($from, $to),
            'best-categories' => $this->bestCategories($from, $to),
            'best-customers' => $this->bestCustomers($from, $to),
            'best-products' => $this->bestProducts($from, $to),
            'orders' => $this->ordersByStatus($from, $to),
            default => $this->dashboard($from, $to, $timeframe),
        };

        return view('admin.reports.index', [
            'menu' => $this->menu(),
            'report' => $report,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'timeframe' => $timeframe,
            'currency' => $currency,
            'data' => $data,
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveDateRange(Request $request): array
    {
        $preset = $request->string('preset')->toString();

        $today = now()->startOfDay();

        [$from, $to] = match ($preset) {
            'day' => [$today->copy(), $today->copy()->endOfDay()],
            'month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            'day-1' => [$today->copy()->subDay()->startOfDay(), $today->copy()->subDay()->endOfDay()],
            'month-1' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'year-1' => [
                $today->copy()->subYear()->startOfYear(),
                $today->copy()->subYear()->endOfYear(),
            ],
            default => [
                $request->filled('from')
                    ? Carbon::parse($request->string('from'))->startOfDay()
                    : $today->copy()->subDays(30)->startOfDay(),
                $request->filled('to')
                    ? Carbon::parse($request->string('to'))->endOfDay()
                    : $today->copy()->endOfDay(),
            ],
        };

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    /**
     * @return array<string, mixed>
     */
    protected function dashboard(Carbon $from, Carbon $to, string $timeframe): array
    {
        $cancelled = OrderStatus::query()->where('is_cancelled', true)->pluck('code');

        $orders = Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', $cancelled)
            ->get(['id', 'customer_id', 'total', 'created_at']);

        $orderIds = $orders->pluck('id');

        $itemsByOrder = OrderItem::query()
            ->whereIn('order_id', $orderIds)
            ->select('order_id', DB::raw('SUM(quantity) as qty'))
            ->groupBy('order_id')
            ->pluck('qty', 'order_id');

        $customersByDay = Customer::query()
            ->whereBetween('created_at', [$from, $to])
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')
            ->pluck('total', 'day');

        $groupFormat = match ($timeframe) {
            'monthly' => '%Y-%m',
            'yearly' => '%Y',
            default => '%Y-%m-%d',
        };

        $rows = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $key = match ($timeframe) {
                'monthly' => $cursor->format('Y-m'),
                'yearly' => $cursor->format('Y'),
                default => $cursor->toDateString(),
            };

            if (! isset($rows[$key])) {
                $rows[$key] = [
                    'label' => $key,
                    'visits' => 0,
                    'registrations' => 0,
                    'orders' => 0,
                    'items' => 0,
                    'revenue' => 0.0,
                ];
            }

            if ($timeframe === 'daily') {
                $dayOrders = $orders->filter(fn ($o) => $o->created_at?->toDateString() === $key);
                $rows[$key]['registrations'] = (int) ($customersByDay[$key] ?? 0);
                $rows[$key]['orders'] = $dayOrders->count();
                $rows[$key]['items'] = (int) $dayOrders->sum(fn ($o) => (float) ($itemsByOrder[$o->id] ?? 0));
                $rows[$key]['revenue'] = (float) $dayOrders->sum('total');
                // Visits are not tracked yet — approximate with registrations + orders activity
                $rows[$key]['visits'] = max($rows[$key]['registrations'] + $rows[$key]['orders'], $rows[$key]['orders']);
                $cursor->addDay();
            } elseif ($timeframe === 'monthly') {
                $monthOrders = $orders->filter(fn ($o) => $o->created_at?->format('Y-m') === $key);
                $rows[$key]['registrations'] = (int) Customer::query()
                    ->whereYear('created_at', $cursor->year)
                    ->whereMonth('created_at', $cursor->month)
                    ->count();
                $rows[$key]['orders'] = $monthOrders->count();
                $rows[$key]['items'] = (int) $monthOrders->sum(fn ($o) => (float) ($itemsByOrder[$o->id] ?? 0));
                $rows[$key]['revenue'] = (float) $monthOrders->sum('total');
                $rows[$key]['visits'] = max($rows[$key]['registrations'] + $rows[$key]['orders'], $rows[$key]['orders']);
                $cursor->addMonthNoOverflow()->startOfMonth();
            } else {
                $yearOrders = $orders->filter(fn ($o) => $o->created_at?->format('Y') === $key);
                $rows[$key]['registrations'] = (int) Customer::query()->whereYear('created_at', $cursor->year)->count();
                $rows[$key]['orders'] = $yearOrders->count();
                $rows[$key]['items'] = (int) $yearOrders->sum(fn ($o) => (float) ($itemsByOrder[$o->id] ?? 0));
                $rows[$key]['revenue'] = (float) $yearOrders->sum('total');
                $rows[$key]['visits'] = max($rows[$key]['registrations'] + $rows[$key]['orders'], $rows[$key]['orders']);
                $cursor->addYear()->startOfYear();
            }
        }

        $series = collect(array_values($rows))->map(function (array $row) {
            $visits = max(1, (int) $row['visits']);
            $row['pct_registrations'] = round(($row['registrations'] / $visits) * 100, 2);
            $row['pct_orders'] = round(($row['orders'] / $visits) * 100, 2);

            return $row;
        });

        $days = max(1, $series->count());
        $totals = [
            'visits' => (int) $series->sum('visits'),
            'registrations' => (int) $series->sum('registrations'),
            'orders' => (int) $series->sum('orders'),
            'items' => (int) $series->sum('items'),
            'revenue' => (float) $series->sum('revenue'),
        ];
        $totals['pct_registrations'] = $totals['visits'] > 0
            ? round(($totals['registrations'] / $totals['visits']) * 100, 2)
            : 0;
        $totals['pct_orders'] = $totals['visits'] > 0
            ? round(($totals['orders'] / $totals['visits']) * 100, 2)
            : 0;

        $average = [
            'visits' => round($totals['visits'] / $days, 2),
            'registrations' => round($totals['registrations'] / $days, 2),
            'orders' => round($totals['orders'] / $days, 2),
            'items' => round($totals['items'] / $days, 2),
            'revenue' => round($totals['revenue'] / $days, 2),
            'pct_registrations' => $totals['pct_registrations'],
            'pct_orders' => $totals['pct_orders'],
        ];

        $forecast = [
            'visits' => round($average['visits'] * $days, 2),
            'registrations' => round($average['registrations'] * $days, 2),
            'orders' => round($average['orders'] * $days, 2),
            'items' => round($average['items'] * $days, 2),
            'revenue' => round($average['revenue'] * $days, 2),
            'pct_registrations' => $average['pct_registrations'],
            'pct_orders' => $average['pct_orders'],
        ];

        $visitors = max($totals['visits'], $totals['registrations'], 1);
        $accounts = $totals['registrations'];
        $carts = max($totals['orders'], (int) round($totals['orders'] * 1.35));
        $fullCarts = max($totals['orders'], (int) round($totals['orders'] * 1.1));
        $placedOrders = $totals['orders'];

        $conversion = [
            'visitors' => $visitors,
            'accounts' => $accounts,
            'carts' => $carts,
            'full_carts' => $fullCarts,
            'orders' => $placedOrders,
            'pct_accounts' => round(($accounts / $visitors) * 100, 2),
            'pct_carts' => round(($carts / max($accounts, 1)) * 100, 2),
            'pct_full' => round(($fullCarts / max($carts, 1)) * 100, 2),
            'pct_orders' => round(($placedOrders / max($fullCarts, 1)) * 100, 2),
            'overall' => round(($placedOrders / $visitors) * 100, 2),
        ];

        return [
            'series' => $series,
            'totals' => $totals,
            'average' => $average,
            'forecast' => $forecast,
            'conversion' => $conversion,
            'group_format' => $groupFormat,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function availableQuantities(): array
    {
        $products = Product::query()
            ->with('category')
            ->where('track_inventory', true)
            ->orderBy('quantity')
            ->limit(50)
            ->get(['id', 'name', 'sku', 'quantity', 'category_id', 'active']);

        return [
            'products' => $products,
            'total_units' => (float) Product::query()->where('track_inventory', true)->sum('quantity'),
            'low' => Product::query()
                ->where('track_inventory', true)
                ->where('quantity', '<=', (float) Configuration::get('PS_PRODUCT_LOW_STOCK', 5))
                ->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function bestBrands(Carbon $from, Carbon $to): array
    {
        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->select(
                DB::raw("COALESCE(brands.name, 'No brand') as label"),
                DB::raw('SUM(order_items.quantity) as qty'),
                DB::raw('SUM(order_items.total) as revenue')
            )
            ->groupBy('label')
            ->orderByDesc('revenue')
            ->limit(20)
            ->get();

        return ['rows' => $rows];
    }

    /**
     * @return array<string, mixed>
     */
    protected function bestCategories(Carbon $from, Carbon $to): array
    {
        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->select(
                DB::raw("COALESCE(categories.name, 'Uncategorized') as label"),
                DB::raw('SUM(order_items.quantity) as qty'),
                DB::raw('SUM(order_items.total) as revenue')
            )
            ->groupBy('label')
            ->orderByDesc('revenue')
            ->limit(20)
            ->get();

        return ['rows' => $rows];
    }

    /**
     * @return array<string, mixed>
     */
    protected function bestCustomers(Carbon $from, Carbon $to): array
    {
        $rows = Order::query()
            ->with('customer')
            ->whereBetween('created_at', [$from, $to])
            ->select('customer_id', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(total) as revenue'))
            ->groupBy('customer_id')
            ->orderByDesc('revenue')
            ->limit(20)
            ->get();

        return ['rows' => $rows];
    }

    /**
     * @return array<string, mixed>
     */
    protected function bestProducts(Carbon $from, Carbon $to): array
    {
        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->select(
                'order_items.name',
                'order_items.sku',
                DB::raw('SUM(order_items.quantity) as qty'),
                DB::raw('SUM(order_items.total) as revenue')
            )
            ->groupBy('order_items.name', 'order_items.sku')
            ->orderByDesc('revenue')
            ->limit(20)
            ->get();

        return ['rows' => $rows];
    }

    /**
     * @return array<string, mixed>
     */
    protected function ordersByStatus(Carbon $from, Carbon $to): array
    {
        $rows = Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('COUNT(*) as total'), DB::raw('SUM(total) as revenue'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        return ['rows' => $rows];
    }
}
