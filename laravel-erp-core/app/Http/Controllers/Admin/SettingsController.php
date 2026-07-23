<?php

namespace App\Http\Controllers\Admin;

use App\Core\Configuration\Configuration;
use App\Core\Location\CountryStateData;
use App\Http\Controllers\Controller;
use App\Models\OrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(?string $group = null): View
    {
        $groups = config('erp.settings_groups', []);
        $group = $group && isset($groups[$group]) ? $group : array_key_first($groups);

        if (! $group || ! isset($groups[$group])) {
            abort(404);
        }

        $definition = $groups[$group];
        if ($group === 'orders') {
            $definition['fields']['PS_ORDER_DEFAULT_STATUS']['options'] = OrderStatus::query()
                ->where('active', true)
                ->orderBy('position')
                ->pluck('name', 'code')
                ->all();
        }
        $values = [];

        foreach ($definition['fields'] as $key => $field) {
            $values[$key] = Configuration::get($key, $field['default'] ?? null);
        }

        return view('admin.settings.index', [
            'groups' => $groups,
            'activeGroup' => $group,
            'definition' => $definition,
            'values' => $values,
        ]);
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        $groups = config('erp.settings_groups', []);

        if (! isset($groups[$group])) {
            abort(404);
        }

        $fields = $groups[$group]['fields'];
        $locations = app(CountryStateData::class);
        $rules = [];

        foreach ($fields as $key => $field) {
            $type = $field['type'] ?? 'text';

            if ($type === 'static') {
                continue;
            }

            if ($key === 'PS_ORDER_DEFAULT_STATUS') {
                $rules[$key] = ['required', Rule::exists('order_statuses', 'code')->where('active', true)];

                continue;
            }
            if ($type === 'image') {
                $rules[$key] = $field['rules'] ?? 'nullable|image|max:2048';
                $rules['remove_'.$key] = ['nullable', 'boolean'];

                continue;
            }
            $rules[$key] = match ($type) {
                'country' => $locations->countryRules(),
                'state' => $locations->stateRules('PS_SHOP_COUNTRY'),
                default => $field['rules'] ?? 'nullable',
            };
        }

        $data = $request->validate($rules);

        foreach ($fields as $key => $field) {
            $type = $field['type'] ?? 'text';

            if ($type === 'static') {
                if ($key === 'PS_CURRENCY_DEFAULT') {
                    Configuration::updateValue($key, config('erp.currency', 'INR'));
                }

                continue;
            }

            if ($type === 'boolean') {
                Configuration::updateValue($key, $request->boolean($key) ? '1' : '0');

                continue;
            }

            if ($type === 'image') {
                $this->updateImageSetting($request, $key);

                continue;
            }

            $value = $data[$key] ?? ($field['default'] ?? '');
            Configuration::updateValue($key, is_bool($value) ? ($value ? '1' : '0') : (string) $value);
        }

        if ($group === 'shop') {
            Configuration::updateValue('PS_CURRENCY_DEFAULT', config('erp.currency', 'INR'));
            Configuration::updateValue('PS_LANG_DEFAULT', config('erp.locale', 'en'));

            if (session()->has('admin_order_cart')) {
                $cart = session('admin_order_cart');
                $cart['currency'] = config('erp.currency', 'INR');
                session(['admin_order_cart' => $cart]);
            }
        }

        return redirect()
            ->route('admin.settings.group', $group)
            ->with('success', $groups[$group]['label'].' saved.');
    }

    protected function updateImageSetting(Request $request, string $key): void
    {
        $current = (string) Configuration::get($key, '');
        $remove = $request->boolean('remove_'.$key);
        $uploaded = $request->file($key);

        if (! $uploaded && ! $remove) {
            return;
        }

        $newPath = $uploaded?->store('shop', 'public');

        if ($remove) {
            Configuration::updateValue($key, '');
        } elseif ($newPath) {
            Configuration::updateValue($key, $newPath);
        }

        if (($remove || $newPath) && $current !== '' && $current !== $newPath) {
            Storage::disk('public')->delete($current);
        }
    }
}
