<?php

namespace App\Http\Controllers\Admin;

use App\Core\Catalog\OrderStockService;
use App\Core\Configuration\Configuration;
use App\Core\Location\CountryStateData;
use App\Core\Mail\ErpMail;
use App\Core\Orders\CreditSlipService;
use App\Core\Orders\DeliverySlipService;
use App\Core\Orders\InvoiceService;
use App\Core\Shipping\ShippingCalculator;
use App\Http\Controllers\Controller;
use App\Models\CreditSlip;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\DeliverySlip;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\OrderStatusHistory;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ShippingCarrier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $currency = Configuration::get('PS_CURRENCY_DEFAULT', 'INR');

        $query = Order::query()->with(['customer', 'invoices', 'deliverySlips'])->latest();

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }
        if ($request->filled('reference')) {
            $query->where('reference', 'like', '%'.$request->string('reference').'%');
        }
        if ($request->filled('customer')) {
            $term = $request->string('customer');
            $query->whereHas('customer', function ($q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('payment')) {
            $query->where('payment_method', 'like', '%'.$request->string('payment').'%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $orders = $query->paginate(20)->withQueryString();

        $cancelledCodes = OrderStatus::query()->where('is_cancelled', true)->pluck('code');
        $validatedCodes = OrderStatus::query()->where('counts_as_validated', true)->pluck('code');
        $sales = (float) Order::query()->whereNotIn('status', $cancelledCodes)->sum('total');
        $ordersCount = Order::query()->count();
        $customersCount = Customer::query()->count();
        $paidCount = Order::query()->whereIn('status', $validatedCodes)->count();

        $kpis = [
            'conversion' => $customersCount > 0 ? round(($paidCount / max($customersCount, 1)) * 100, 1) : 0,
            'pending' => Order::query()->where('status', 'pending')->count(),
            'avg_order' => $ordersCount > 0 ? $sales / $ordersCount : 0,
            'sales' => $sales,
            'currency' => $currency,
            'count' => $orders->total(),
        ];

        $statuses = OrderStatus::query()->where('active', true)->orderBy('position')->get();

        return view('admin.orders.index', compact('orders', 'kpis', 'statuses'));
    }

    public function create(Request $request): View
    {
        $cart = $this->cart();
        $customer = ! empty($cart['customer_id'])
            ? Customer::query()->find($cart['customer_id'])
            : null;

        $customerResults = collect();
        if ($request->filled('customer_q')) {
            $q = $request->string('customer_q');
            $customerResults = Customer::query()
                ->where(function ($query) use ($q) {
                    $query->where('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('customer_code', 'like', "%{$q}%");
                })
                ->orderBy('first_name')
                ->limit(20)
                ->get();
        }

        $productResults = collect();
        if ($request->filled('product_q')) {
            $q = $request->string('product_q');
            $productResults = Product::query()
                ->where('active', true)
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%");
                })
                ->orderBy('name')
                ->limit(20)
                ->get();
        }

        $lines = $this->cartLines($cart);
        $currency = $cart['currency'] ?? Configuration::get('PS_CURRENCY_DEFAULT', 'INR');
        $productsSubtotal = (float) collect($lines)->sum('base_total');
        $discountTotal = (float) collect($lines)->sum('discount_total');
        $subtotal = $productsSubtotal - $discountTotal;
        $productTax = (string) Configuration::get('PS_TAX_ENABLED', '0') === '1'
            ? round($subtotal * ((float) Configuration::get('PS_TAX_RATE_DEFAULT', 0) / 100), 2)
            : 0.0;
        $shipping = $this->shippingContext($lines, $customer, $subtotal);

        return view('admin.orders.create', [
            'cart' => $cart,
            'customer' => $customer,
            'customerResults' => $customerResults,
            'productResults' => $productResults,
            'lines' => $lines,
            'subtotal' => $subtotal,
            'productsSubtotal' => $productsSubtotal,
            'discountTotal' => $discountTotal,
            'productTax' => $productTax,
            'shippingQuotes' => $shipping['quotes'],
            'shippingWeight' => $shipping['weight'],
            'defaultCarrierId' => Configuration::get('PS_SHIPPING_DEFAULT_CARRIER', ''),
            'orderStatuses' => OrderStatus::query()->where('active', true)->orderBy('position')->get(),
            'currency' => $currency,
            'customer_q' => $request->string('customer_q')->toString(),
            'product_q' => $request->string('product_q')->toString(),
        ]);
    }

    public function selectCustomer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
        ]);

        $cart = $this->cart();
        $cart['customer_id'] = (int) $data['customer_id'];
        $this->saveCart($cart);

        return redirect()->route('admin.orders.create')->with('success', 'Customer selected.');
    }

    public function storeCustomer(Request $request): RedirectResponse
    {
        $locations = app(CountryStateData::class);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('customers', 'email'),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => $locations->stateRules(),
            'country' => $locations->countryRules(),
        ]);

        $customer = Customer::query()->create([
            ...$data,
            'customer_code' => Configuration::get('PS_CUSTOMER_CODE_PREFIX', 'CUS').'-'.strtoupper(Str::random(6)),
            'customer_group_id' => CustomerGroup::query()->where('active', true)->orderBy('position')->value('id'),
            'type' => Configuration::get('PS_CUSTOMER_DEFAULT_GROUP', 'individual'),
            'active' => true,
        ]);

        if (! empty($customer->email)) {
            ErpMail::send($customer->email, 'Welcome — your account was created', 'emails.customer-created', [
                'customer' => $customer,
            ]);
        }

        $cart = $this->cart();
        $cart['customer_id'] = $customer->id;
        $this->saveCart($cart);

        return redirect()->route('admin.orders.create')->with('success', 'Customer created and selected.');
    }

    public function addProduct(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $cart = $this->cart();
        $cart['lines'] ??= [];
        $found = false;
        $requestedQuantity = (float) $data['quantity'];

        foreach ($cart['lines'] as &$line) {
            if ((int) $line['product_id'] === (int) $data['product_id']) {
                $requestedQuantity += (float) $line['qty'];
                $line['qty'] = $requestedQuantity;
                $found = true;
                break;
            }
        }
        unset($line);

        $product = Product::query()->findOrFail($data['product_id']);
        app(OrderStockService::class)->ensureAvailable($product, $requestedQuantity);

        if (! $found) {
            $cart['lines'][] = [
                'product_id' => (int) $data['product_id'],
                'qty' => (float) $data['quantity'],
            ];
        }

        $this->saveCart($cart);

        return redirect()->route('admin.orders.create')->with('success', 'Product added to cart.');
    }

    public function pdfByDate(Request $request): Response
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);
        $invoices = Invoice::query()
            ->with(['order', 'customer'])
            ->whereDate('issued_at', '>=', $data['from'])
            ->whereDate('issued_at', '<=', $data['to'])
            ->orderBy('issued_at')
            ->get();
        if ($invoices->isEmpty()) {
            return back()->with('error', 'No invoices found for this date range.');
        }
        $pdf = Pdf::loadView('admin.orders.invoices-bulk-pdf', [
            'invoices' => $invoices,
            'from' => $data['from'],
            'to' => $data['to'],
            'title' => 'Invoices by date',
        ])->setPaper('a4');
        return $pdf->download('invoices-'.$data['from'].'-to-'.$data['to'].'.pdf');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:orders,id'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['ids'])));
        $count = count($ids);

        if ($data['action'] === 'delete') {
            DB::transaction(function () use ($ids) {
                Order::query()->whereIn('id', $ids)->each(function (Order $order) {
                    $order->delete();
                });
            });

            return back()->with('success', $count.' order(s) deleted.');
        }

        return back()->with('error', 'Unknown bulk action.');
    }

    public function pdfByStatus(Request $request): Response
    {
        $data = $request->validate([
            'statuses' => ['required', 'array', 'min:1'],
            'statuses.*' => ['integer', 'exists:order_statuses,id'],
        ]);

        $codes = OrderStatus::query()
            ->whereIn('id', $data['statuses'])
            ->pluck('code');

        $invoices = Invoice::query()
            ->with(['order', 'customer'])
            ->whereHas('order', fn ($q) => $q->whereIn('status', $codes))
            ->orderBy('id')
            ->get();

        if ($invoices->isEmpty()) {
            return back()->with('error', 'No invoices for selected statuses.');
        }

        $pdf = Pdf::loadView('admin.orders.invoices-bulk-pdf', [
            'invoices' => $invoices,
            'title' => 'Invoices by status',
        ])->setPaper('a4');

        return $pdf->download('invoices-by-status.pdf');
    }

    public function removeProduct(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
        ]);

        $cart = $this->cart();
        $cart['lines'] = array_values(array_filter(
            $cart['lines'] ?? [],
            fn ($line) => (int) $line['product_id'] !== (int) $data['product_id']
        ));
        $this->saveCart($cart);

        return redirect()->route('admin.orders.create')->with('success', 'Product removed.');
    }

    public function clearCart(): RedirectResponse
    {
        session()->forget('admin_order_cart');

        return redirect()->route('admin.orders.create')->with('success', 'Cart cleared.');
    }

    public function store(Request $request): RedirectResponse
    {
        $notesRequired = (string) Configuration::get('PS_ORDER_NOTES_REQUIRED', '0') === '1';
        $data = $request->validate([
            'status' => ['required', Rule::exists('order_statuses', 'code')->where('active', true)],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'shipping_carrier_id' => ['nullable', 'exists:shipping_carriers,id'],
            'notes' => [$notesRequired ? 'required' : 'nullable', 'string', 'max:1000'],
        ]);

        $cart = $this->cart();
        $lines = $this->cartLines($cart);

        if ($lines === []) {
            return back()->with('error', 'Add at least one product to the cart.');
        }

        $prefix = Configuration::get('PS_ORDER_PREFIX', 'ORD');
        $currency = $cart['currency'] ?? Configuration::get('PS_CURRENCY_DEFAULT', 'INR');
        $productsSubtotal = (float) collect($lines)->sum('base_total');
        $discountTotal = (float) collect($lines)->sum('discount_total');
        $subtotal = $productsSubtotal - $discountTotal;
        $productTax = (string) Configuration::get('PS_TAX_ENABLED', '0') === '1'
            ? round($subtotal * ((float) Configuration::get('PS_TAX_RATE_DEFAULT', 0) / 100), 2)
            : 0.0;
        $minimum = (float) Configuration::get('PS_ORDER_MIN_AMOUNT', 0);
        if ($minimum > 0 && $subtotal < $minimum) {
            throw ValidationException::withMessages([
                'order' => 'The minimum order amount is '.number_format($minimum, 2).' '.$currency.'.',
            ]);
        }
        $customer = ! empty($cart['customer_id']) ? Customer::query()->find($cart['customer_id']) : null;
        $shippingContext = $this->shippingContext($lines, $customer, (float) $subtotal);
        $carrierId = $data['shipping_carrier_id']
            ?? Configuration::get('PS_SHIPPING_DEFAULT_CARRIER');
        $shippingQuote = $shippingContext['quotes']->first(
            fn (array $quote) => (int) $quote['carrier']->id === (int) $carrierId
        );

        if ($carrierId && ! $shippingQuote) {
            throw ValidationException::withMessages([
                'shipping_carrier_id' => 'This carrier is not available for the destination, order value, weight, or package size.',
            ]);
        }

        $stock = app(OrderStockService::class);
        foreach ($lines as $line) {
            $stock->ensureAvailable($line['product'], (float) $line['qty']);
        }

        $order = DB::transaction(function () use ($data, $lines, $prefix, $currency, $productsSubtotal, $discountTotal, $subtotal, $productTax, $customer, $cart, $stock, $shippingContext, $shippingQuote) {
            $shippingCost = (float) ($shippingQuote['cost'] ?? 0);
            $shippingTax = (float) ($shippingQuote['tax'] ?? 0);
            $order = Order::query()->create([
                'reference' => $prefix.'-'.strtoupper(Str::random(8)),
                'customer_id' => $cart['customer_id'] ?? null,
                'employee_id' => auth()->user()?->employee?->id,
                'status' => $data['status'],
                'subtotal' => $productsSubtotal,
                'tax_total' => $productTax + $shippingTax,
                'discount_total' => $discountTotal,
                'total' => $subtotal + $productTax + $shippingCost + $shippingTax,
                'currency' => $currency,
                'payment_method' => $data['payment_method'] ?? null,
                'shipping_carrier_id' => $shippingQuote['carrier']->id ?? null,
                'shipping_carrier_name' => $shippingQuote['carrier']->name ?? null,
                'shipping_cost' => $shippingCost,
                'shipping_tax' => $shippingTax,
                'shipping_weight' => $shippingContext['weight'],
                'delivery_country' => $customer?->country,
                'notes' => $data['notes'] ?? null,
                'ordered_at' => now(),
            ]);

            foreach ($lines as $line) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $line['product']->id,
                    'name' => $line['product']->name,
                    'sku' => $line['product']->sku,
                    'quantity' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'total' => $line['total'],
                ]);

                $stock->decrementForOrderLine($line['product'], (float) $line['qty']);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'employee_id' => auth()->user()?->employee?->id,
                'status' => $data['status'],
                'comment' => 'Order created',
            ]);

            return $order;
        });

        $initialStatus = OrderStatus::query()->where('code', $order->status)->first();
        if (
            $initialStatus?->is_paid
            && (string) Configuration::get('PS_ORDER_AUTO_INVOICE', '1') === '1'
        ) {
            try {
                app(InvoiceService::class)->createForOrder($order, 'Automatically generated for paid order.');
            } catch (Throwable) {
                // Invoices disabled or already exists — skip silently on create.
            }
        }

        session()->forget('admin_order_cart');

        $order->load(['customer', 'items']);

        if ($order->customer?->email) {
            ErpMail::send($order->customer->email, 'Order confirmation '.$order->reference, 'emails.order-created', [
                'order' => $order,
                'customerName' => $order->customer->full_name,
            ]);
        }

        $shopEmail = Configuration::get('PS_SHOP_EMAIL') ?: Configuration::get('PS_MAIL_FROM_ADDRESS');
        if ($shopEmail && $shopEmail !== ($order->customer?->email)) {
            ErpMail::send($shopEmail, 'New order '.$order->reference, 'emails.order-created', [
                'order' => $order,
                'customerName' => $order->customer?->full_name ?: 'Guest',
            ]);
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order '.$order->reference.' created.');
    }

    public function show(Order $order): View
    {
        $order->load([
            'customer',
            'items.product',
            'employee',
            'invoices',
            'creditSlips',
            'deliverySlips',
            'statusHistories.employee',
            'payments.employee',
        ]);

        $validatedCodes = OrderStatus::query()->where('counts_as_validated', true)->pluck('code');
        $cancelledCodes = OrderStatus::query()->where('is_cancelled', true)->pluck('code');
        $customerOrdersCount = $order->customer_id
            ? Order::query()->where('customer_id', $order->customer_id)->whereIn('status', $validatedCodes)->count()
            : 0;

        $customerSpent = $order->customer_id
            ? (float) Order::query()->where('customer_id', $order->customer_id)->whereNotIn('status', $cancelledCodes)->sum('total')
            : 0;

        $paidAmount = (float) $order->payments->sum('amount');

        return view('admin.orders.show', [
            'order' => $order,
            'statuses' => OrderStatus::query()
                ->where(fn ($query) => $query->where('active', true)->orWhere('code', $order->status))
                ->orderBy('position')
                ->get(),
            'customerOrdersCount' => $customerOrdersCount,
            'customerSpent' => $customerSpent,
            'paidAmount' => $paidAmount,
            'tab' => request('tab', 'status'),
            'invoicesEnabled' => app(InvoiceService::class)->enabled(),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::exists('order_statuses', 'code')->where('active', true)],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);
        $status = OrderStatus::query()->where('code', $data['status'])->firstOrFail();

        $order->update(['status' => $data['status']]);

        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'employee_id' => auth()->user()?->employee?->id,
            'status' => $data['status'],
            'comment' => $data['comment'] ?? null,
        ]);

        if (
            $status->is_paid
            && (string) Configuration::get('PS_ORDER_AUTO_INVOICE', '1') === '1'
            && ! $order->invoices()->exists()
        ) {
            try {
                app(InvoiceService::class)->createForOrder($order, 'Automatically generated when order became paid.');
            } catch (Throwable) {
                // Invoices disabled — skip.
            }
        }

        // When status is Delivered: auto-create invoice + delivery slip if missing
        if ($status->is_delivered) {
            // Invoice (only if none yet)
            if (! $order->invoices()->exists()) {
                try {
                    app(InvoiceService::class)->createForOrder(
                        $order,
                        'Automatically generated when order was marked delivered.'
                    );
                } catch (Throwable) {
                    // Invoices disabled or already exists — skip
                }
            }

            // Delivery slip (only if none yet)
            if (! $order->deliverySlips()->exists()) {
                app(DeliverySlipService::class)->createForOrder(
                    $order,
                    carrier: $order->shipping_carrier_name,
                    trackingNumber: null,
                    status: 'delivered',
                    notes: 'Automatically generated when order was marked delivered.'
                );
            }
        }

        $order->loadMissing('customer');
        if ($status->send_email && $order->customer?->email) {
            ErpMail::send($order->customer->email, 'Order '.$order->reference.' status update', 'emails.order-status', [
                'order' => $order,
                'customerName' => $order->customer->full_name,
                'statusLabel' => $status->name,
                'comment' => $data['comment'] ?? null,
            ]);
        }

        return redirect()
            ->route('admin.orders.show', ['order' => $order, 'tab' => 'status'])
            ->with('success', 'Order status updated.');
    }

    public function storePayment(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', 'string', 'max:100'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        OrderPayment::query()->create([
            'order_id' => $order->id,
            'employee_id' => auth()->user()?->employee?->id,
            'payment_method' => $data['payment_method'],
            'transaction_id' => $data['transaction_id'] ?? null,
            'amount' => $data['amount'],
            'currency' => $order->currency,
            'paid_at' => now(),
        ]);

        if (! $order->payment_method) {
            $order->update(['payment_method' => $data['payment_method']]);
        }

        return redirect()
            ->route('admin.orders.show', ['order' => $order, 'tab' => 'payment'])
            ->with('success', 'Payment added.');
    }

    public function updateNote(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $order->update(['notes' => $data['notes'] ?? null]);

        return back()->with('success', 'Order note saved.');
    }

    public function createInvoiceFromOrder(Order $order): RedirectResponse
    {
        try {
            app(InvoiceService::class)->createForOrder($order);
        } catch (Throwable $e) {
            return redirect()
                ->route('admin.orders.show', ['order' => $order, 'tab' => 'documents'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.orders.show', ['order' => $order, 'tab' => 'documents'])
            ->with('success', 'Invoice generated for this order.');
    }

    public function invoices(): View
    {
        $invoices = Invoice::query()->with(['order', 'customer'])->latest('id')->paginate(20);
        $orders = Order::query()->whereDoesntHave('invoices')->latest('id')->limit(50)->get();
        $statuses = OrderStatus::query()->where('active', true)->orderBy('position')->get();

        $options = [
            'enabled' => (string) Configuration::get('PS_INVOICE', '1') === '1',
            'tax_breakdown' => (string) Configuration::get('PS_INVOICE_TAXES_BREAKDOWN', '0') === '1',
            'product_image' => (string) Configuration::get('PS_INVOICE_PRODUCT_IMAGE', '0') === '1',
            'prefix' => Configuration::get('PS_INVOICE_PREFIX', '#IN'),
            'year_active' => (string) Configuration::get('PS_INVOICE_YEAR_ACTIVE', '0') === '1',
            'year_reset' => (string) Configuration::get('PS_INVOICE_RESET', '0') === '1',
            'year_position' => Configuration::get('PS_INVOICE_YEAR_POS', 'after'),
            'next_number' => (int) Configuration::get('PS_INVOICE_NUMBER', 0),
            'legal_text' => Configuration::get('PS_INVOICE_LEGAL_FREE_TEXT', ''),
            'footer_text' => Configuration::get('PS_INVOICE_FREE_TEXT', ''),
            'model' => Configuration::get('PS_INVOICE_MODEL', 'invoice'),
            'use_disk' => (string) Configuration::get('PS_PDF_USE_DISK_CACHE', '0') === '1',
        ];
        return view('admin.orders.invoices', compact(
            'invoices',
            'orders',
            'statuses',
            'options'
        ));
    }

    public function updateInvoiceOptions(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'tax_breakdown' => ['nullable', 'boolean'],
            'product_image' => ['nullable', 'boolean'],
            'prefix' => ['required', 'string', 'max:32'],
            'year_active' => ['nullable', 'boolean'],
            'year_reset' => ['nullable', 'boolean'],
            'year_position' => ['required', 'in:before,after'],
            'next_number' => ['required', 'integer', 'min:0'],
            'legal_text' => ['nullable', 'string'],
            'footer_text' => ['nullable', 'string'],
            'model' => ['required', 'string', 'max:50'],
            'use_disk' => ['nullable', 'boolean'],
        ]);

        Configuration::updateValue('PS_INVOICE', $request->boolean('enabled') ? '1' : '0');
        Configuration::updateValue('PS_INVOICE_TAXES_BREAKDOWN', $request->boolean('tax_breakdown') ? '1' : '0');
        Configuration::updateValue('PS_INVOICE_PRODUCT_IMAGE', $request->boolean('product_image') ? '1' : '0');
        Configuration::updateValue('PS_INVOICE_PREFIX', $data['prefix']);
        Configuration::updateValue('PS_INVOICE_YEAR_ACTIVE', $request->boolean('year_active') ? '1' : '0');
        Configuration::updateValue('PS_INVOICE_RESET', $request->boolean('year_reset') ? '1' : '0');
        Configuration::updateValue('PS_INVOICE_YEAR_POS', $data['year_position']);
        Configuration::updateValue('PS_INVOICE_NUMBER', (string) $data['next_number']);
        Configuration::updateValue('PS_INVOICE_LEGAL_FREE_TEXT', $data['legal_text'] ?? '');
        Configuration::updateValue('PS_INVOICE_FREE_TEXT', $data['footer_text'] ?? '');
        Configuration::updateValue('PS_INVOICE_MODEL', $data['model']);
        Configuration::updateValue('PS_PDF_USE_DISK_CACHE', $request->boolean('use_disk') ? '1' : '0');

        return back()->with('success', 'Invoice options updated.');
    }

    public function storeInvoice(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = Order::query()->findOrFail($data['order_id']);

        try {
            app(InvoiceService::class)->createForOrder($order, $data['notes'] ?? null);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Invoice generated.');
    }

    public function creditSlips(): View
    {
        $slips = CreditSlip::query()->with(['order', 'customer'])->latest('id')->paginate(20);
        $orders = Order::query()->with('customer')->latest('id')->limit(100)->get();
        $options = [
            'prefix' => Configuration::get('PS_CREDIT_SLIP_PREFIX', '#CR'),
            'next_number' => (int) Configuration::get('PS_CREDIT_SLIP_NUMBER', 0),
        ];

        return view('admin.orders.credit-slips', compact('slips', 'orders', 'options'));
    }

    public function updateCreditSlipOptions(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'prefix' => ['required', 'string', 'max:32'],
            'next_number' => ['required', 'integer', 'min:0'],
        ]);

        Configuration::updateValue('PS_CREDIT_SLIP_PREFIX', $data['prefix']);
        Configuration::updateValue('PS_CREDIT_SLIP_NUMBER', (string) $data['next_number']);

        return back()->with('success', 'Credit slip options saved.');
    }

    public function pdfCreditSlipsByDate(Request $request): Response
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $slips = CreditSlip::query()
            ->with(['order', 'customer'])
            ->whereDate('issued_at', '>=', $data['from'])
            ->whereDate('issued_at', '<=', $data['to'])
            ->orderBy('issued_at')
            ->get();

        if ($slips->isEmpty()) {
            return back()->with('error', 'No credit slips found for this date range.');
        }

        $pdf = Pdf::loadView('admin.orders.credit-slips-bulk-pdf', [
            'slips' => $slips,
            'from' => $data['from'],
            'to' => $data['to'],
            'title' => 'Credit slips by date',
        ])->setPaper('a4');

        return $pdf->download('credit-slips-'.$data['from'].'-to-'.$data['to'].'.pdf');
    }

    public function storeCreditSlip(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $order = Order::query()->findOrFail($data['order_id']);

        app(CreditSlipService::class)->createForOrder(
            $order,
            (float) $data['amount'],
            $data['reason'] ?? null
        );

        return back()->with('success', 'Credit slip created.');
    }

    public function createCreditSlipFromOrder(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $amount = isset($data['amount']) ? (float) $data['amount'] : (float) $order->total;

        app(CreditSlipService::class)->createForOrder(
            $order,
            $amount,
            $data['reason'] ?? 'Credit slip generated from order'
        );

        return redirect()
            ->route('admin.orders.show', ['order' => $order, 'tab' => 'documents'])
            ->with('success', 'Credit slip generated for this order.');
    }

    public function deliverySlips(): View
    {
        $slips = DeliverySlip::query()->with(['order.shippingCarrier', 'customer'])->latest('id')->paginate(20);
        $orders = Order::query()->with(['customer', 'shippingCarrier'])->latest('id')->limit(100)->get();
        $carriers = ShippingCarrier::query()->where('active', true)->orderBy('position')->orderBy('name')->get();
        $options = [
            'prefix' => Configuration::get('PS_DELIVERY_PREFIX', '#DF'),
            'next_number' => (int) Configuration::get('PS_DELIVERY_NUMBER', 1),
            'product_image' => (string) Configuration::get('PS_PDF_IMG_DELIVERY', '0') === '1',
        ];

        return view('admin.orders.delivery-slips', compact('slips', 'orders', 'carriers', 'options'));
    }

    public function updateDeliverySlipOptions(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'prefix' => ['required', 'string', 'max:32'],
            'next_number' => ['required', 'integer', 'min:0'],
            'product_image' => ['nullable', 'boolean'],
        ]);

        Configuration::updateValue('PS_DELIVERY_PREFIX', $data['prefix']);
        Configuration::updateValue('PS_DELIVERY_NUMBER', (string) $data['next_number']);
        Configuration::updateValue('PS_PDF_IMG_DELIVERY', $request->boolean('product_image') ? '1' : '0');

        return back()->with('success', 'Delivery slip options saved.');
    }

    public function pdfDeliverySlipsByDate(Request $request): Response
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $slips = DeliverySlip::query()
            ->with(['order', 'customer'])
            ->whereDate('shipped_at', '>=', $data['from'])
            ->whereDate('shipped_at', '<=', $data['to'])
            ->orderBy('shipped_at')
            ->orderBy('id')
            ->get();

        if ($slips->isEmpty()) {
            return back()->with('error', 'No delivery slips found for this date range.');
        }

        $pdf = Pdf::loadView('admin.orders.delivery-slips-bulk-pdf', [
            'slips' => $slips,
            'from' => $data['from'],
            'to' => $data['to'],
            'title' => 'Delivery slips by date',
        ])->setPaper('a4');

        return $pdf->download('delivery-slips-'.$data['from'].'-to-'.$data['to'].'.pdf');
    }

    public function storeDeliverySlip(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'carrier' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:prepared,shipped,delivered'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = Order::query()->findOrFail($data['order_id']);

        app(DeliverySlipService::class)->createForOrder(
            $order,
            $data['carrier'] ?? null,
            $data['tracking_number'] ?? null,
            $data['status'],
            $data['notes'] ?? null
        );

        return back()->with('success', 'Delivery slip created.');
    }

    public function createDeliverySlipFromOrder(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'carrier' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:prepared,shipped,delivered'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        app(DeliverySlipService::class)->createForOrder(
            $order,
            $data['carrier'] ?? null,
            $data['tracking_number'] ?? null,
            $data['status'] ?? 'shipped',
            $data['notes'] ?? null
        );

        return redirect()
            ->route('admin.orders.show', ['order' => $order, 'tab' => 'documents'])
            ->with('success', 'Delivery slip generated for this order.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function cart(): array
    {
        $cart = session('admin_order_cart', [
            'customer_id' => null,
            'currency' => Configuration::get('PS_CURRENCY_DEFAULT', 'INR'),
            'lines' => [],
        ]);

        // Always use the live shop currency for open carts.
        $cart['currency'] = (string) Configuration::get('PS_CURRENCY_DEFAULT', 'INR');

        return $cart;
    }

    /**
     * @param  array<string, mixed>  $cart
     */
    protected function saveCart(array $cart): void
    {
        session(['admin_order_cart' => $cart]);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array{quotes: \Illuminate\Support\Collection, weight: float, dimensions: array{width: float, height: float, depth: float}}
     */
    protected function shippingContext(array $lines, ?Customer $customer, float $subtotal): array
    {
        $weight = 0.0;
        $dimensions = ['width' => 0.0, 'height' => 0.0, 'depth' => 0.0];

        foreach ($lines as $line) {
            $product = $line['product'];
            if ($product->isVirtual()) {
                continue;
            }

            $weight += (float) $product->weight * (float) $line['qty'];
            $dimensions['width'] = max($dimensions['width'], (float) $product->width);
            $dimensions['height'] = max($dimensions['height'], (float) $product->height);
            $dimensions['depth'] = max($dimensions['depth'], (float) $product->depth);
        }

        $countryCode = app(CountryStateData::class)->countryCode($customer?->country);
        $carriers = ShippingCarrier::query()
            ->where('active', true)
            ->with('rateRanges')
            ->orderBy('position')
            ->get();

        return [
            'quotes' => app(ShippingCalculator::class)->available(
                $carriers,
                $subtotal,
                $weight,
                $countryCode,
                $dimensions
            ),
            'weight' => round($weight, 3),
            'dimensions' => $dimensions,
        ];
    }

    /**
     * @param  array<string, mixed>  $cart
     * @return list<array<string, mixed>>
     */
    protected function cartLines(array $cart): array
    {
        $lines = [];
        $discountPercent = 0.0;
        if (! empty($cart['customer_id'])) {
            $customer = Customer::query()->with('group')->find($cart['customer_id']);
            if ($customer?->group?->active) {
                $discountPercent = (float) $customer->group->discount_percent;
            }
        }

        foreach ($cart['lines'] ?? [] as $line) {
            $product = Product::query()->find($line['product_id'] ?? 0);
            if (! $product) {
                continue;
            }

            $qty = (float) ($line['qty'] ?? 0);
            $baseUnit = (float) $product->price;
            $unit = round($baseUnit * (1 - ($discountPercent / 100)), 2);
            $baseTotal = round($qty * $baseUnit, 2);
            $total = round($qty * $unit, 2);
            $lines[] = [
                'product' => $product,
                'qty' => $qty,
                'unit_price' => $unit,
                'base_total' => $baseTotal,
                'discount_total' => round($baseTotal - $total, 2),
                'discount_percent' => $discountPercent,
                'total' => $total,
            ];
        }

        return $lines;
    }
}
