<?php

namespace App\Http\Controllers\Admin;

use App\Core\Configuration\Configuration;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $currency = Configuration::get('PS_CURRENCY_DEFAULT', 'INR');
        $cancelledCodes = OrderStatus::query()->where('is_cancelled', true)->pluck('code');
        $validatedCodes = OrderStatus::query()->where('counts_as_validated', true)->pluck('code');

        $salesTotal = (float) Order::query()->whereNotIn('status', $cancelledCodes)->sum('total');
        $ordersCount = Order::query()->count();
        $customersCount = Customer::query()->count();
        $productsCount = Product::query()->count();
        $employeesCount = Employee::query()->count();
        $avgOrder = $ordersCount > 0 ? $salesTotal / $ordersCount : 0;

        $paidOrders = Order::query()->whereIn('status', $validatedCodes)->count();
        $conversion = $customersCount > 0 ? round(($paidOrders / max($customersCount, 1)) * 100, 1) : 0;

        $stats = [
            'sales' => $salesTotal,
            'orders' => $ordersCount,
            'cart_value' => $avgOrder,
            'customers' => $customersCount,
            'products' => $productsCount,
            'employees' => $employeesCount,
            'conversion' => $conversion,
            'net_profit' => $salesTotal * 0.35,
            'currency' => $currency,
        ];

        $activity = [
            'new_customers' => Customer::query()->where('created_at', '>=', now()->subDays(30))->count(),
            'total_customers' => $customersCount,
            'new_orders' => Order::query()->where('created_at', '>=', now()->subDays(30))->count(),
            'pending_orders' => Order::query()->where('status', Configuration::get('PS_ORDER_DEFAULT_STATUS', 'pending'))->count(),
            'low_stock' => Product::query()
                ->where('track_inventory', true)
                ->where('quantity', '<=', (float) Configuration::get('PS_PRODUCT_LOW_STOCK', 5))
                ->count(),
        ];

        $statusBreakdown = Order::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $salesChart = $this->salesLastDays(30);
        $ordersByDay = $this->ordersLastDays(14);

        $recentOrders = Order::query()->with('customer')->latest()->limit(8)->get();

        return view('admin.dashboard.index', compact(
            'stats',
            'activity',
            'statusBreakdown',
            'salesChart',
            'ordersByDay',
            'recentOrders'
        ));
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    protected function salesLastDays(int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $rows = Order::query()
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('SUM(total) as amount'))
            ->where('created_at', '>=', $start)
            ->where('status', '!=', 'cancelled')
            ->groupBy('day')
            ->pluck('amount', 'day')
            ->all();

        $labels = [];
        $values = [];

        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $key = $day->toDateString();
            $labels[] = $day->format('M j');
            $values[] = round((float) ($rows[$key] ?? 0), 2);
        }

        return compact('labels', 'values');
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    protected function ordersLastDays(int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $rows = Order::query()
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $start)
            ->groupBy('day')
            ->pluck('total', 'day')
            ->all();

        $labels = [];
        $values = [];

        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $key = $day->toDateString();
            $labels[] = $day->format('D');
            $values[] = (int) ($rows[$key] ?? 0);
        }

        return compact('labels', 'values');
    }
}
