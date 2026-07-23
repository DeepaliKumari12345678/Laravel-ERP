<?php

namespace App\Http\Controllers\Admin;

use App\Core\Configuration\Configuration;
use App\Core\Location\CountryStateData;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        $query = CustomerAddress::query()->with('customer')->latest();

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }
        if ($request->filled('first_name')) {
            $query->where('first_name', 'like', '%'.$request->string('first_name').'%');
        }
        if ($request->filled('last_name')) {
            $query->where('last_name', 'like', '%'.$request->string('last_name').'%');
        }
        if ($request->filled('address')) {
            $term = $request->string('address');
            $query->where(function ($q) use ($term) {
                $q->where('address1', 'like', "%{$term}%")
                    ->orWhere('address2', 'like', "%{$term}%");
            });
        }
        if ($request->filled('postcode')) {
            $query->where('postcode', 'like', '%'.$request->string('postcode').'%');
        }
        if ($request->filled('city')) {
            $query->where('city', 'like', '%'.$request->string('city').'%');
        }
        if ($request->filled('country')) {
            $query->where('country', $request->string('country'));
        }

        $addresses = $query->paginate(20)->withQueryString();
        $countries = CustomerAddress::query()
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct()
            ->orderBy('country')
            ->pluck('country');

        return view('admin.addresses.index', compact('addresses', 'countries'));
    }

    public function create(Request $request): View
    {
        $customers = Customer::query()->orderBy('first_name')->orderBy('last_name')->get();
        $address = new CustomerAddress([
            'customer_id' => $request->integer('customer_id') ?: null,
            'alias' => 'My Address',
            'country' => Configuration::get('PS_SHOP_COUNTRY', 'United States') ?: 'United States',
            'state' => Configuration::get('PS_SHOP_STATE', ''),
        ]);

        if ($address->customer_id) {
            $customer = $customers->firstWhere('id', $address->customer_id);
            if ($customer) {
                $address->first_name = $customer->first_name;
                $address->last_name = $customer->last_name;
                $address->company = $customer->company;
                $address->phone = $customer->phone;
            }
        }

        return view('admin.addresses.form', [
            'address' => $address,
            'customers' => $customers,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->syncDefault($data);

        $address = CustomerAddress::query()->create($data);

        return redirect()
            ->route('admin.addresses.index')
            ->with('success', 'Address #'.$address->id.' created.');
    }

    public function edit(CustomerAddress $address): View
    {
        $address->load('customer');
        $customers = Customer::query()->orderBy('first_name')->orderBy('last_name')->get();

        return view('admin.addresses.form', [
            'address' => $address,
            'customers' => $customers,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, CustomerAddress $address): RedirectResponse
    {
        $data = $this->validated($request);
        $this->syncDefault($data, $address->id);

        $address->update($data);

        return redirect()
            ->route('admin.addresses.index')
            ->with('success', 'Address updated.');
    }

    public function destroy(CustomerAddress $address): RedirectResponse
    {
        $address->delete();

        return redirect()
            ->route('admin.addresses.index')
            ->with('success', 'Address deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $locations = app(CountryStateData::class);

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'alias' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:150'],
            'dni' => ['nullable', 'string', 'max:64'],
            'vat_number' => ['nullable', 'string', 'max:64'],
            'address1' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'postcode' => ['required', 'string', 'max:30'],
            'city' => ['required', 'string', 'max:100'],
            'state' => $locations->stateRules(),
            'country' => $locations->countryRules(required: true),
            'phone' => ['nullable', 'string', 'max:50'],
            'phone_mobile' => ['nullable', 'string', 'max:50'],
            'other' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $data['is_default'] = $request->boolean('is_default');

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function syncDefault(array $data, ?int $ignoreId = null): void
    {
        if (empty($data['is_default'])) {
            return;
        }

        $query = CustomerAddress::query()->where('customer_id', $data['customer_id']);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $query->update(['is_default' => false]);
    }
}
