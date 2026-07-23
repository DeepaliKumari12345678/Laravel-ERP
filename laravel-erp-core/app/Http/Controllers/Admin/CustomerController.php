<?php

namespace App\Http\Controllers\Admin;

use App\Core\Configuration\Configuration;
use App\Core\Location\CountryStateData;
use App\Core\Mail\ErpMail;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\CustomerTitle;
use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $currency = Configuration::get('PS_CURRENCY_DEFAULT', 'INR');
        $cancelledCodes = OrderStatus::query()->where('is_cancelled', true)->pluck('code');

        $query = Customer::query()
            ->with('group')
            ->withSum(['orders as sales_total' => function ($q) use ($cancelledCodes) {
                $q->whereNotIn('status', $cancelledCodes);
            }], 'total')
            ->latest();

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }
        if ($request->filled('first_name')) {
            $query->where('first_name', 'like', '%'.$request->string('first_name').'%');
        }
        if ($request->filled('last_name')) {
            $query->where('last_name', 'like', '%'.$request->string('last_name').'%');
        }
        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.$request->string('email').'%');
        }
        if ($request->filled('active')) {
            $query->where('active', $request->string('active') === '1');
        }
        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }
        if ($request->filled('customer_group_id')) {
            $query->where('customer_group_id', $request->integer('customer_group_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $customers = $query->paginate(20)->withQueryString();

        $all = Customer::query();
        $total = (clone $all)->count();
        $active = (clone $all)->where('active', true)->count();
        $withOrders = (clone $all)->whereHas('orders')->count();
        $newsletter = (clone $all)->where('newsletter', true)->count();
        $avgOrders = $total > 0
            ? round(Order::query()->whereNotNull('customer_id')->count() / $total, 2)
            : 0;

        return view('admin.customers.index', [
            'customers' => $customers,
            'groups' => CustomerGroup::query()->orderBy('position')->get(),
            'currency' => $currency,
            'kpis' => [
                'total' => $total,
                'active' => $active,
                'with_orders' => $withOrders,
                'avg_orders' => $avgOrders,
                'newsletter' => $newsletter,
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.customers.form', [
            'customer' => new Customer([
                'active' => true,
                'type' => Configuration::get('PS_CUSTOMER_DEFAULT_GROUP', 'individual'),
                'social_title' => CustomerTitle::query()->where('active', true)->orderBy('position')->value('name'),
                'customer_group_id' => CustomerGroup::query()->where('active', true)->orderBy('position')->value('id'),
            ]),
            'groups' => CustomerGroup::query()->where('active', true)->orderBy('position')->get(),
            'titles' => CustomerTitle::query()->where('active', true)->orderBy('position')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $prefix = Configuration::get('PS_CUSTOMER_CODE_PREFIX', 'CUS');

        $customer = Customer::query()->create([
            ...$data,
            'customer_code' => $prefix.'-'.strtoupper(Str::random(6)),
        ]);

        if (! empty($customer->email)) {
            ErpMail::send($customer->email, 'Welcome — your account was created', 'emails.customer-created', [
                'customer' => $customer,
            ]);
        }

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Customer created.');
    }

    public function show(Customer $customer): View
    {
        $customer->load(['group', 'addresses', 'orders' => fn ($q) => $q->latest()->limit(20)]);
        $currency = Configuration::get('PS_CURRENCY_DEFAULT', 'INR');

        $validCodes = OrderStatus::query()->where('counts_as_validated', true)->pluck('code');
        $invalidCodes = OrderStatus::query()->where('is_cancelled', true)->pluck('code');
        $validOrders = $customer->orders->whereIn('status', $validCodes)->count();
        $invalidOrders = $customer->orders->whereIn('status', $invalidCodes)->count();

        return view('admin.customers.show', [
            'customer' => $customer,
            'currency' => $currency,
            'totalSpent' => $customer->totalSpent(),
            'validOrders' => $validOrders,
            'invalidOrders' => $invalidOrders,
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('admin.customers.form', [
            'customer' => $customer,
            'groups' => CustomerGroup::query()->orderBy('position')->get(),
            'titles' => CustomerTitle::query()->orderBy('position')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validated($request, $customer));

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->orders()->exists()) {
            return back()->with('error', 'Cannot delete a customer who has orders. Disable them instead.');
        }

        $customer->addresses()->delete();
        $customer->delete();

        return redirect()
            ->route('admin.customers.index')
            ->with('success', 'Customer deleted.');
    }

    public function updateNote(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $customer->update(['note' => $data['note'] ?? null]);

        return back()->with('success', 'Private note saved.');
    }

    public function toggle(Request $request, Customer $customer): RedirectResponse
    {
        $field = $request->validate([
            'field' => ['required', 'in:active,newsletter,partner_offers'],
        ])['field'];

        $customer->update([$field => ! $customer->{$field}]);

        return back()->with('success', ucfirst(str_replace('_', ' ', $field)).' updated.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Customer $customer = null): array
    {
        $phoneRequired = Configuration::get('PS_CUSTOMER_REQUIRE_PHONE', '0') === '1';

        $locations = app(CountryStateData::class);

        $data = $request->validate([
            'social_title' => ['nullable', Rule::exists('customer_titles', 'name')],
            'customer_group_id' => ['nullable', Rule::exists('customer_groups', 'id')],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('customers', 'email')->ignore($customer?->id),
            ],
            'phone' => [$phoneRequired ? 'required' : 'nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:150'],
            'type' => ['required', 'in:individual,company'],
            'birthday' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => $locations->stateRules(),
            'postcode' => ['nullable', 'string', 'max:30'],
            'country' => $locations->countryRules(),
            'active' => ['nullable', 'boolean'],
            'newsletter' => ['nullable', 'boolean'],
            'partner_offers' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['active'] = $request->boolean('active');
        $data['newsletter'] = $request->boolean('newsletter');
        $data['partner_offers'] = $request->boolean('partner_offers');

        return $data;
    }
}
