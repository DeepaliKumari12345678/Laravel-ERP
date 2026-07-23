<?php

namespace App\Http\Controllers\Admin;

use App\Core\Configuration\Configuration;
use App\Core\Location\CountryStateData;
use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\ShippingCarrier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ImproveController extends Controller
{
    public function carriers(Request $request): View
    {
        $query = ShippingCarrier::query()->withCount('rateRanges')->orderBy('position')->orderBy('name');

        if ($request->filled('id')) {
            $query->whereKey($request->integer('id'));
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->string('name').'%');
        }
        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        return view('admin.improve.carriers', [
            'carriers' => $query->paginate(20)->withQueryString(),
            'currency' => Configuration::get('PS_CURRENCY_DEFAULT', 'INR'),
        ]);
    }

    public function createCarrier(): View
    {
        return $this->carrierForm(new ShippingCarrier([
            'active' => true,
            'speed_grade' => 0,
            'billing_basis' => 'price',
            'apply_handling_cost' => true,
            'out_of_range_behavior' => 'disable',
            'position' => (int) ShippingCarrier::query()->max('position') + 1,
        ]), 'create');
    }

    public function storeCarrier(Request $request): RedirectResponse
    {
        $data = $this->validatedCarrier($request);
        $ranges = $data['ranges'];
        unset($data['ranges'], $data['logo'], $data['remove_logo']);
        $data['logo_path'] = $request->file('logo')?->store('shipping/carriers', 'public');

        DB::transaction(function () use ($data, $ranges) {
            $carrier = ShippingCarrier::query()->create($data);
            $this->syncCarrierRanges($carrier, $ranges);
        });

        return redirect()->route('admin.shipping.carriers')->with('success', 'Carrier created.');
    }

    public function editCarrier(ShippingCarrier $carrier): View
    {
        $carrier->load('rateRanges');

        return $this->carrierForm($carrier, 'edit');
    }

    public function updateCarrier(Request $request, ShippingCarrier $carrier): RedirectResponse
    {
        $data = $this->validatedCarrier($request);
        $ranges = $data['ranges'];
        unset($data['ranges'], $data['logo'], $data['remove_logo']);

        $newLogo = $request->file('logo')?->store('shipping/carriers', 'public');
        $oldLogo = $carrier->logo_path;
        if ($request->boolean('remove_logo')) {
            $data['logo_path'] = null;
        } elseif ($newLogo) {
            $data['logo_path'] = $newLogo;
        }

        DB::transaction(function () use ($carrier, $data, $ranges) {
            $carrier->update($data);
            $this->syncCarrierRanges($carrier, $ranges);
        });

        if (($newLogo || $request->boolean('remove_logo')) && $oldLogo) {
            Storage::disk('public')->delete($oldLogo);
        }

        return redirect()->route('admin.shipping.carriers')->with('success', 'Carrier updated.');
    }

    public function destroyCarrier(ShippingCarrier $carrier): RedirectResponse
    {
        $carrier->deleteLogo();
        $carrier->delete();

        return back()->with('success', 'Carrier deleted.');
    }

    public function shippingPreferences(): View
    {
        return view('admin.improve.shipping-preferences', [
            'carriers' => ShippingCarrier::query()->where('active', true)->orderBy('position')->orderBy('name')->get(),
            'currency' => Configuration::get('PS_CURRENCY_DEFAULT', 'INR'),
            'values' => [
                'PS_SHIPPING_HANDLING' => Configuration::get('PS_SHIPPING_HANDLING', 0),
                'PS_SHIPPING_FREE_PRICE' => Configuration::get('PS_SHIPPING_FREE_PRICE', 0),
                'PS_SHIPPING_FREE_WEIGHT' => Configuration::get('PS_SHIPPING_FREE_WEIGHT', 0),
                'PS_SHIPPING_DEFAULT_CARRIER' => Configuration::get('PS_SHIPPING_DEFAULT_CARRIER', ''),
                'PS_SHIPPING_SORT_BY' => Configuration::get('PS_SHIPPING_SORT_BY', 'position'),
            ],
        ]);
    }

    public function updateShippingPreferences(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'PS_SHIPPING_HANDLING' => ['required', 'numeric', 'min:0'],
            'PS_SHIPPING_FREE_PRICE' => ['required', 'numeric', 'min:0'],
            'PS_SHIPPING_FREE_WEIGHT' => ['required', 'numeric', 'min:0'],
            'PS_SHIPPING_DEFAULT_CARRIER' => ['nullable', 'exists:shipping_carriers,id'],
            'PS_SHIPPING_SORT_BY' => ['required', Rule::in(['position', 'price', 'name'])],
        ]);

        foreach ($data as $key => $value) {
            Configuration::updateValue($key, (string) ($value ?? ''));
        }

        return back()->with('success', 'Shipping preferences saved.');
    }

    protected function carrierForm(ShippingCarrier $carrier, string $mode): View
    {
        return view('admin.improve.carrier-form', [
            'carrier' => $carrier,
            'mode' => $mode,
            'countries' => app(CountryStateData::class)->countries(),
            'currency' => Configuration::get('PS_CURRENCY_DEFAULT', 'INR'),
            'weightUnit' => Configuration::get('PS_PRODUCT_WEIGHT_UNIT', 'kg'),
            'dimensionUnit' => Configuration::get('PS_PRODUCT_DIMENSION_UNIT', 'cm'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedCarrier(Request $request): array
    {
        $countryCodes = array_keys(app(CountryStateData::class)->countries());
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'delay' => ['required', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'tracking_url' => ['nullable', 'url', 'max:500'],
            'speed_grade' => ['required', 'integer', 'min:0', 'max:9'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'position' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'active' => ['nullable', 'boolean'],
            'billing_basis' => ['required', Rule::in(['price', 'weight'])],
            'free_shipping' => ['nullable', 'boolean'],
            'apply_handling_cost' => ['nullable', 'boolean'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'out_of_range_behavior' => ['required', Rule::in(['disable', 'highest'])],
            'country_codes' => ['nullable', 'array'],
            'country_codes.*' => ['string', Rule::in($countryCodes)],
            'max_width' => ['nullable', 'numeric', 'min:0'],
            'max_height' => ['nullable', 'numeric', 'min:0'],
            'max_depth' => ['nullable', 'numeric', 'min:0'],
            'max_weight' => ['nullable', 'numeric', 'min:0'],
            'ranges' => ['nullable', 'array'],
            'ranges.*.from_value' => ['nullable', 'numeric', 'min:0'],
            'ranges.*.to_value' => ['nullable', 'numeric', 'gt:ranges.*.from_value'],
            'ranges.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $ranges = collect($data['ranges'] ?? [])
            ->filter(fn (array $range) => filled($range['to_value'] ?? null))
            ->map(fn (array $range) => [
                'from_value' => (float) ($range['from_value'] ?? 0),
                'to_value' => (float) $range['to_value'],
                'price' => (float) ($range['price'] ?? 0),
            ])
            ->sortBy('from_value')
            ->values();

        $previousEnd = null;
        foreach ($ranges as $range) {
            if ($previousEnd !== null && $range['from_value'] < $previousEnd) {
                throw ValidationException::withMessages([
                    'ranges' => 'Shipping ranges cannot overlap.',
                ]);
            }
            $previousEnd = $range['to_value'];
        }

        return [
            ...$data,
            'ranges' => $ranges->all(),
            'price' => $data['price'] ?? 0,
            'currency' => Configuration::get('PS_CURRENCY_DEFAULT', 'INR'),
            'position' => $data['position'] ?? 0,
            'active' => $request->boolean('active'),
            'free_shipping' => $request->boolean('free_shipping'),
            'apply_handling_cost' => $request->boolean('apply_handling_cost'),
            'tax_rate' => $data['tax_rate'] ?? 0,
            'country_codes' => array_values(array_unique($data['country_codes'] ?? [])),
            'max_width' => $data['max_width'] ?? 0,
            'max_height' => $data['max_height'] ?? 0,
            'max_depth' => $data['max_depth'] ?? 0,
            'max_weight' => $data['max_weight'] ?? 0,
        ];
    }

    /**
     * @param  list<array{from_value: float, to_value: float, price: float}>  $ranges
     */
    protected function syncCarrierRanges(ShippingCarrier $carrier, array $ranges): void
    {
        $carrier->rateRanges()->delete();

        foreach ($ranges as $range) {
            $carrier->rateRanges()->create($range);
        }
    }

    public function payments(Request $request): View
    {
        $methods = PaymentMethod::query()
            ->when($request->filled('id'), fn ($q) => $q->where('id', (int) $request->input('id')))
            ->when($request->filled('name'), fn ($q) => $q->where('name', 'like', '%'.$request->string('name').'%'))
            ->when($request->filled('code'), fn ($q) => $q->where('code', 'like', '%'.$request->string('code').'%'))
            ->when($request->filled('active'), fn ($q) => $q->where('active', $request->input('active') === '1'))
            ->orderBy('position')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.improve.payments', compact('methods'));
    }

    public function createPayment(): View
    {
        return view('admin.improve.payment-form', [
            'method' => new PaymentMethod([
                'active' => true,
                'position' => 0,
            ]),
            'mode' => 'create',
        ]);
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $data = $this->validatedPayment($request);

        PaymentMethod::query()->create($data);

        return redirect()
            ->route('admin.payment.methods')
            ->with('success', 'Payment method created.');
    }

    public function editPayment(PaymentMethod $payment): View
    {
        return view('admin.improve.payment-form', [
            'method' => $payment,
            'mode' => 'edit',
        ]);
    }

    public function updatePayment(Request $request, PaymentMethod $payment): RedirectResponse
    {
        $payment->update($this->validatedPayment($request, $payment));

        return redirect()
            ->route('admin.payment.methods')
            ->with('success', 'Payment method updated.');
    }

    public function togglePayment(PaymentMethod $payment): RedirectResponse
    {
        $payment->update(['active' => ! $payment->active]);

        return back()->with('success', 'Payment method status updated.');
    }

    public function destroyPayment(PaymentMethod $payment): RedirectResponse
    {
        $payment->delete();

        return redirect()
            ->route('admin.payment.methods')
            ->with('success', 'Payment method deleted.');
    }

    /**
     * @return array{name: string, code: string, description: ?string, position: int, active: bool}
     */
    protected function validatedPayment(Request $request, ?PaymentMethod $payment = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                $payment ? 'required' : 'nullable',
                'string',
                'max:50',
                'unique:payment_methods,code'.($payment ? ','.$payment->id : ''),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'position' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => $data['name'],
            'code' => $data['code'] ?: Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'position' => (int) ($data['position'] ?? 0),
            'active' => $request->boolean('active', true),
        ];
    }
}
