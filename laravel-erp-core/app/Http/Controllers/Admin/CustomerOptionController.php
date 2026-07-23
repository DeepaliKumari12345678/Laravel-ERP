<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\CustomerTitle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerOptionController extends Controller
{
    public function groups(Request $request): View
    {
        $query = CustomerGroup::query()->withCount('customers')->orderBy('position')->orderBy('name');

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->string('name').'%');
        }
        if ($request->filled('discount_percent')) {
            $query->where('discount_percent', $request->input('discount_percent'));
        }
        if ($request->filled('members')) {
            $query->having('customers_count', '=', $request->integer('members'));
        }
        if ($request->filled('show_prices')) {
            $query->where('show_prices', $request->string('show_prices') === '1');
        }
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->date('created_from'));
        }
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->date('created_to'));
        }

        return view('admin.customers.groups', [
            'groups' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function createGroup(): View
    {
        return view('admin.customers.group-form', [
            'group' => new CustomerGroup([
                'discount_percent' => 0,
                'show_prices' => true,
                'price_display_method' => 'tax_excluded',
                'active' => true,
                'position' => 0,
                'meta' => [
                    'category_discounts' => [],
                ],
            ]),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'mode' => 'create',
        ]);
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        CustomerGroup::query()->create($this->validatedGroup($request));

        return redirect()
            ->route('admin.customer-groups.index')
            ->with('success', 'Customer group created.');
    }

    public function editGroup(CustomerGroup $customerGroup): View
    {
        return view('admin.customers.group-form', [
            'group' => $customerGroup,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'mode' => 'edit',
        ]);
    }

    public function updateGroup(Request $request, CustomerGroup $customerGroup): RedirectResponse
    {
        $customerGroup->update($this->validatedGroup($request, $customerGroup));

        return redirect()
            ->route('admin.customer-groups.edit', $customerGroup)
            ->with('success', 'Customer group updated.');
    }

    public function destroyGroup(CustomerGroup $customerGroup): RedirectResponse
    {
        if ($customerGroup->is_system) {
            return back()->with('error', 'System groups cannot be deleted.');
        }

        if ($customerGroup->customers()->exists()) {
            return back()->with('error', 'Move customers to another group before deleting this group.');
        }

        $customerGroup->delete();

        return redirect()
            ->route('admin.customer-groups.index')
            ->with('success', 'Customer group deleted.');
    }

    public function titles(Request $request): View
    {
        $query = CustomerTitle::query()->orderBy('position')->orderBy('name');

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->string('name').'%');
        }
        if ($request->filled('gender')) {
            $query->where('gender', $request->string('gender'));
        }

        return view('admin.customers.titles', [
            'titles' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function createTitle(): View
    {
        return view('admin.customers.title-form', [
            'title' => new CustomerTitle([
                'gender' => 'male',
                'image_width' => 16,
                'image_height' => 16,
                'active' => true,
                'position' => 0,
            ]),
            'mode' => 'create',
        ]);
    }

    public function storeTitle(Request $request): RedirectResponse
    {
        $title = CustomerTitle::query()->create($this->validatedTitle($request));
        $this->updateTitleImage($request, $title);

        return redirect()
            ->route('admin.customer-titles.index')
            ->with('success', 'Customer title created.');
    }

    public function editTitle(CustomerTitle $customerTitle): View
    {
        return view('admin.customers.title-form', [
            'title' => $customerTitle,
            'mode' => 'edit',
        ]);
    }

    public function updateTitle(Request $request, CustomerTitle $customerTitle): RedirectResponse
    {
        $oldName = $customerTitle->name;
        $data = $this->validatedTitle($request, $customerTitle);
        $customerTitle->update($data);
        $this->updateTitleImage($request, $customerTitle);

        if ($oldName !== $data['name']) {
            Customer::query()->where('social_title', $oldName)->update(['social_title' => $data['name']]);
        }

        return redirect()
            ->route('admin.customer-titles.edit', $customerTitle)
            ->with('success', 'Customer title updated.');
    }

    public function bulkTitles(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:customer_titles,id'],
        ]);

        $deleted = 0;
        $skipped = 0;

        foreach (CustomerTitle::query()->whereIn('id', $data['ids'])->get() as $title) {
            if (Customer::query()->where('social_title', $title->name)->exists()) {
                $skipped++;
                continue;
            }
            $this->deleteTitleImage($title);
            $title->delete();
            $deleted++;
        }

        $message = $deleted.' title(s) deleted.';
        if ($skipped > 0) {
            $message .= ' '.$skipped.' skipped (assigned to customers).';
        }

        return back()->with($deleted ? 'success' : 'error', $message);
    }

    public function destroyTitle(CustomerTitle $customerTitle): RedirectResponse
    {
        if (Customer::query()->where('social_title', $customerTitle->name)->exists()) {
            return back()->with('error', 'This title is assigned to customers. Disable it instead.');
        }

        $this->deleteTitleImage($customerTitle);
        $customerTitle->delete();

        return redirect()
            ->route('admin.customer-titles.index')
            ->with('success', 'Customer title deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedGroup(Request $request, ?CustomerGroup $group = null): array
    {
        $nameRule = Rule::unique('customer_groups', 'name');
        if ($group) {
            $nameRule->ignore($group);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', $nameRule],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'price_display_method' => ['required', Rule::in(['tax_excluded', 'tax_included'])],
            'show_prices' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'position' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
            'category_discounts' => ['nullable', 'array'],
            'category_discounts.*.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_discounts.*.discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $categoryDiscounts = [];
        foreach ($data['category_discounts'] ?? [] as $row) {
            if (empty($row['category_id'])) {
                continue;
            }
            $categoryDiscounts[] = [
                'category_id' => (int) $row['category_id'],
                'discount' => round((float) ($row['discount'] ?? 0), 2),
            ];
        }

        return [
            'name' => $data['name'],
            'discount_percent' => $data['discount_percent'],
            'price_display_method' => $data['price_display_method'],
            'show_prices' => $request->boolean('show_prices'),
            'description' => $data['description'] ?? null,
            'position' => $data['position'] ?? ($group?->position ?? 0),
            'active' => $request->boolean('active', true),
            'meta' => [
                'category_discounts' => $categoryDiscounts,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedTitle(Request $request, ?CustomerTitle $title = null): array
    {
        $nameRule = Rule::unique('customer_titles', 'name');
        if ($title) {
            $nameRule->ignore($title);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', $nameRule],
            'gender' => ['required', Rule::in(['male', 'female', 'neutral'])],
            'image' => ['nullable', 'image', 'max:2048'],
            'image_width' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'image_height' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'position' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => $data['name'],
            'gender' => $data['gender'],
            'image_width' => (int) ($data['image_width'] ?? 16),
            'image_height' => (int) ($data['image_height'] ?? 16),
            'position' => $data['position'] ?? ($title?->position ?? 0),
            'active' => $request->boolean('active', true),
        ];
    }

    protected function updateTitleImage(Request $request, CustomerTitle $title): void
    {
        if ($request->boolean('remove_image') && $title->image_path) {
            Storage::disk('public')->delete($title->image_path);
            $title->update(['image_path' => null]);
        }

        if ($request->hasFile('image')) {
            if ($title->image_path) {
                Storage::disk('public')->delete($title->image_path);
            }
            $path = $request->file('image')->store('customer-titles', 'public');
            $title->update(['image_path' => $path]);
        }
    }

    protected function deleteTitleImage(CustomerTitle $title): void
    {
        if ($title->image_path) {
            Storage::disk('public')->delete($title->image_path);
        }
    }
}
