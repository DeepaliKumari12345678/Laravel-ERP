<?php

namespace App\Http\Controllers\Admin;

use App\Core\Configuration\Configuration;
use App\Core\Location\CountryStateData;
use App\Http\Controllers\Controller;
use App\Models\AttributeGroup;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\BrandAddress;
use App\Models\Category;
use App\Models\CustomerGroup;
use App\Models\Feature;
use App\Models\FeatureValue;
use App\Models\InventoryMovement;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductCombination;
use App\Models\ProductPackItem;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function products(Request $request): View
    {
        $currency = Configuration::get('PS_CURRENCY_DEFAULT', 'INR');
        $lowStock = (float) Configuration::get('PS_PRODUCT_LOW_STOCK', '5');

        $query = Product::query()->with(['category', 'brand', 'supplier'])->latest();

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->string('name').'%');
        }
        if ($request->filled('sku')) {
            $query->where('sku', 'like', '%'.$request->string('sku').'%');
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->integer('brand_id'));
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }
        if ($request->filled('active')) {
            $query->where('active', $request->string('active') === '1');
        }

        $products = $query->paginate(20)->withQueryString();
        $categories = Category::query()->orderBy('name')->get();

        return view('admin.catalog.products', [
            'products' => $products,
            'categories' => $categories,
            'brands' => Brand::query()->where('active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::query()->where('active', true)->orderBy('name')->get(),
            'currency' => $currency,
            'typeOptions' => Product::typeOptions(),
            'kpis' => [
                'total' => Product::query()->count(),
                'active' => Product::query()->where('active', true)->count(),
                'low_stock' => Product::query()->where('track_inventory', true)->where('quantity', '<=', $lowStock)->count(),
                'packs' => Product::query()->where('type', Product::TYPE_PACK)->count(),
                'virtual' => Product::query()->where('type', Product::TYPE_VIRTUAL)->count(),
            ],
        ]);
    }

    public function createProduct(): View
    {
        return view('admin.catalog.product-form', [
            'product' => new Product([
                'active' => true,
                'track_inventory' => true,
                'type' => Configuration::get('PS_PRODUCT_DEFAULT_TYPE', 'product'),
                'quantity' => 0,
                'price' => 0,
            ]),
            'categories' => Category::query()->orderBy('name')->get(),
            'brands' => Brand::query()->where('active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::query()->where('active', true)->orderBy('name')->get(),
            'packProducts' => $this->packComponentCandidates(),
            'featuresCatalog' => $this->featuresCatalog(),
            'attributeGroups' => $this->attributeGroupsCatalog(),
            'typeOptions' => Product::typeOptions(),
            'mode' => 'create',
        ]);
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $data = $this->validatedProduct($request);
        $packItems = $data['pack_items'] ?? [];
        $featureRows = $data['product_features'] ?? [];
        $combinationRows = $data['combinations'] ?? [];
        unset(
            $data['pack_items'],
            $data['product_features'],
            $data['combinations'],
            $data['virtual_file'],
            $data['remove_virtual_file']
        );

        $product = DB::transaction(function () use ($request, $data, $packItems, $featureRows, $combinationRows) {
            $product = Product::query()->create([
                ...$data,
                'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            ]);

            $this->updateProductImage($request, $product);
            $this->updateVirtualFile($request, $product);
            $this->syncPackItems($product, $packItems);
            $this->syncProductFeatures($product, $featureRows);
            $this->syncProductCombinations($product, $combinationRows);

            return $product;
        });

        return redirect()
            ->route('admin.catalog.products')
            ->with('success', 'Product created.');
    }

    public function editProduct(Product $product): View
    {
        $product->load([
            'packItems.item',
            'features',
            'combinations.attributeValues.group',
        ]);

        return view('admin.catalog.product-form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'packProducts' => $this->packComponentCandidates($product->id),
            'featuresCatalog' => $this->featuresCatalog(),
            'attributeGroups' => $this->attributeGroupsCatalog(),
            'typeOptions' => Product::typeOptions(),
            'mode' => 'edit',
        ]);
    }

    public function previewProduct(Product $product): View
    {
        $product->load([
            'category',
            'brand',
            'supplier',
            'packItems.item',
            'features',
            'combinations.attributeValues.group',
        ]);

        $featureValues = FeatureValue::query()
            ->whereIn('id', $product->features->pluck('pivot.feature_value_id')->filter())
            ->get()
            ->keyBy('id');

        return view('admin.catalog.product-preview', [
            'product' => $product,
            'featureValues' => $featureValues,
            'currency' => Configuration::get('PS_CURRENCY_DEFAULT', 'INR'),
            'typeOptions' => Product::typeOptions(),
        ]);
    }

    public function duplicateProduct(Product $product): RedirectResponse
    {
        $product->load('packItems');

        $copy = DB::transaction(function () use ($product) {
            $attrs = $product->only([
                'category_id',
                'brand_id',
                'supplier_id',
                'supplier_sku',
                'description',
                'price',
                'cost',
                'weight',
                'width',
                'height',
                'depth',
                'type',
                'track_inventory',
                'quantity',
                'active',
                'meta',
                'download_limit',
                'download_expiry_days',
                'download_expires_at',
            ]);

            $attrs['name'] = $product->name.' (copy)';
            $attrs['sku'] = $product->sku
                ? Str::limit($product->sku.'-COPY-'.Str::upper(Str::random(4)), 64, '')
                : null;
            $attrs['slug'] = Str::slug($attrs['name']).'-'.Str::lower(Str::random(4));
            $attrs['image_path'] = null;
            $attrs['virtual_file_path'] = null;
            $attrs['virtual_file_name'] = null;

            if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                $ext = pathinfo($product->image_path, PATHINFO_EXTENSION);
                $newPath = 'products/'.Str::uuid().($ext ? '.'.$ext : '');
                Storage::disk('public')->copy($product->image_path, $newPath);
                $attrs['image_path'] = $newPath;
            }

            $copy = Product::query()->create($attrs);

            if ($product->type === Product::TYPE_PACK) {
                foreach ($product->packItems as $item) {
                    ProductPackItem::query()->create([
                        'pack_product_id' => $copy->id,
                        'item_product_id' => $item->item_product_id,
                        'quantity' => $item->quantity,
                    ]);
                }
            }

            return $copy;
        });

        return redirect()
            ->route('admin.catalog.products.edit', $copy)
            ->with('success', 'Product duplicated. You can edit the copy now.');
    }

    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedProduct($request, $product);
        $packItems = $data['pack_items'] ?? [];
        $featureRows = $data['product_features'] ?? [];
        $combinationRows = $data['combinations'] ?? [];
        unset(
            $data['pack_items'],
            $data['product_features'],
            $data['combinations'],
            $data['virtual_file'],
            $data['remove_virtual_file']
        );

        DB::transaction(function () use ($request, $product, $data, $packItems, $featureRows, $combinationRows) {
            $previousType = $product->type;
            $product->update($data);
            $this->updateProductImage($request, $product);
            $this->updateVirtualFile($request, $product);
            $this->syncPackItems($product, $packItems);
            $this->syncProductFeatures($product, $featureRows);
            $this->syncProductCombinations($product, $combinationRows);

            if ($previousType === Product::TYPE_PACK && $product->type !== Product::TYPE_PACK) {
                $product->packItems()->delete();
            }

            if ($previousType === Product::TYPE_VIRTUAL && $product->type !== Product::TYPE_VIRTUAL) {
                $product->deleteVirtualFile();
                $product->update([
                    'download_limit' => null,
                    'download_expiry_days' => null,
                    'download_expires_at' => null,
                ]);
            }
        });

        return redirect()
            ->route('admin.catalog.products')
            ->with('success', 'Product updated.');
    }

    public function destroyProduct(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            return back()->with('error', 'Cannot delete a product used on orders. Disable it instead.');
        }

        if ($product->packMemberships()->exists()) {
            return back()->with('error', 'Cannot delete a product used inside a pack. Remove it from packs first.');
        }

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        if ($product->virtual_file_path) {
            Storage::disk('local')->delete($product->virtual_file_path);
        }

        $product->delete();

        return redirect()
            ->route('admin.catalog.products')
            ->with('success', 'Product deleted.');
    }

    public function bulkProducts(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:enable,disable,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:products,id'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['ids'])));
        $products = Product::query()->whereIn('id', $ids)->get();

        if ($data['action'] === 'enable') {
            Product::query()->whereIn('id', $ids)->update(['active' => true]);

            return back()->with('success', $products->count().' product(s) enabled.');
        }

        if ($data['action'] === 'disable') {
            Product::query()->whereIn('id', $ids)->update(['active' => false]);

            return back()->with('success', $products->count().' product(s) disabled.');
        }

        $deleted = 0;
        $skipped = 0;

        foreach ($products as $product) {
            if ($product->orderItems()->exists() || $product->packMemberships()->exists()) {
                $skipped++;
                continue;
            }

            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            if ($product->virtual_file_path) {
                Storage::disk('local')->delete($product->virtual_file_path);
            }

            $product->delete();
            $deleted++;
        }

        if ($deleted === 0 && $skipped > 0) {
            return back()->with('error', 'No products deleted. Selected items are used on orders or packs.');
        }

        $message = $deleted.' product(s) deleted.';
        if ($skipped > 0) {
            $message .= ' '.$skipped.' skipped (used on orders/packs).';
        }

        return back()->with('success', $message);
    }

    public function toggleProduct(Request $request, Product $product): RedirectResponse
    {
        $field = $request->validate([
            'field' => ['required', 'in:active,track_inventory'],
        ])['field'];

        $product->update([$field => ! $product->{$field}]);

        return back()->with('success', 'Product updated.');
    }

    public function categories(Request $request): View
    {
        $parentId = $request->filled('parent_id') ? $request->integer('parent_id') : null;
        $currentCategory = $parentId
            ? Category::query()->with('parent')->find($parentId)
            : null;

        $query = Category::query()
            ->withCount(['products', 'children'])
            ->with('parent')
            ->orderBy('position')
            ->orderBy('name');

        // Drill-down: root list vs children of a category
        if (! $request->filled('id') && ! $request->filled('name') && ! $request->filled('description')) {
            if ($parentId) {
                $query->where('parent_id', $parentId);
            } else {
                $query->whereNull('parent_id');
            }
        }

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->string('name').'%');
        }
        if ($request->filled('description')) {
            $query->where('description', 'like', '%'.$request->string('description').'%');
        }
        if ($request->filled('position')) {
            $query->where('position', $request->integer('position'));
        }
        if ($request->filled('active')) {
            $query->where('active', $request->string('active') === '1');
        }

        $topCategory = Category::query()
            ->withCount('products')
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->first();

        $categoryCount = max(1, Category::query()->count());
        $productAssigned = Product::query()->whereNotNull('category_id')->count();

        return view('admin.catalog.categories', [
            'categories' => $query->paginate(20)->withQueryString(),
            'currentCategory' => $currentCategory,
            'kpis' => [
                'disabled' => Category::query()->where('active', false)->count(),
                'empty' => Category::query()->doesntHave('products')->count(),
                'top_name' => $topCategory?->name ?? '—',
                'avg_products' => round($productAssigned / $categoryCount, 1),
                'total' => Category::query()->count(),
            ],
        ]);
    }

    public function createCategory(Request $request): View
    {
        $parentId = $request->filled('parent_id') ? $request->integer('parent_id') : null;

        return view('admin.catalog.category-form', [
            'category' => new Category([
                'active' => true,
                'position' => 0,
                'meta' => [],
                'parent_id' => $parentId,
            ]),
            'parents' => Category::query()->orderBy('name')->get(),
            'groups' => CustomerGroup::query()->orderBy('name')->get(),
            'mode' => 'create',
        ]);
    }

    public function editCategory(Category $category): View
    {
        return view('admin.catalog.category-form', [
            'category' => $category,
            'parents' => Category::query()->where('id', '!=', $category->id)->orderBy('name')->get(),
            'groups' => CustomerGroup::query()->orderBy('name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $this->validatedCategory($request);

        $category = DB::transaction(function () use ($request, $data) {
            $category = Category::query()->create([
                ...$data,
                'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            ]);
            $this->updateCategoryImages($request, $category);

            return $category;
        });

        return redirect()
            ->route('admin.catalog.categories.edit', $category)
            ->with('success', 'Category created.');
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validatedCategory($request, $category);

        DB::transaction(function () use ($request, $category, $data) {
            $category->update($data);
            $this->updateCategoryImages($request, $category);
        });

        return redirect()
            ->route('admin.catalog.categories.edit', $category)
            ->with('success', 'Category updated.');
    }

    public function toggleCategory(Category $category): RedirectResponse
    {
        $category->update(['active' => ! $category->active]);

        return back()->with('success', 'Category updated.');
    }

    public function bulkCategories(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:enable,disable,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['ids'])));
        $categories = Category::query()->whereIn('id', $ids)->get();

        if ($data['action'] === 'enable') {
            Category::query()->whereIn('id', $ids)->update(['active' => true]);

            return back()->with('success', $categories->count().' categor'.($categories->count() === 1 ? 'y' : 'ies').' enabled.');
        }

        if ($data['action'] === 'disable') {
            Category::query()->whereIn('id', $ids)->update(['active' => false]);

            return back()->with('success', $categories->count().' categor'.($categories->count() === 1 ? 'y' : 'ies').' disabled.');
        }

        $deleted = 0;
        $skipped = 0;

        foreach ($categories as $category) {
            if ($category->products()->exists() || $category->children()->exists()) {
                $skipped++;
                continue;
            }

            $this->deleteCategoryImages($category);
            $category->delete();
            $deleted++;
        }

        if ($deleted === 0 && $skipped > 0) {
            return back()->with('error', 'No categories deleted. Selected items have products or child categories.');
        }

        $message = $deleted.' categor'.($deleted === 1 ? 'y' : 'ies').' deleted.';
        if ($skipped > 0) {
            $message .= ' '.$skipped.' skipped (have products/children).';
        }

        return back()->with('success', $message);
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        if ($category->products()->exists() || $category->children()->exists()) {
            return back()->with('error', 'Category has products or child categories. Move them first.');
        }

        $this->deleteCategoryImages($category);
        $category->delete();

        return redirect()
            ->route('admin.catalog.categories')
            ->with('success', 'Category deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedCategory(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                ...($category ? ['not_in:'.$category->id] : []),
            ],
            'description' => ['nullable', 'string', 'max:10000'],
            'additional_description' => ['nullable', 'string', 'max:10000'],
            'position' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
            'slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'redirect_type' => ['nullable', 'in:301,302,404,410'],
            'redirect_category_id' => ['nullable', 'exists:categories,id'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:customer_groups,id'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_cover_image' => ['nullable', 'boolean'],
            'remove_thumbnail' => ['nullable', 'boolean'],
        ]);

        $slug = filled($data['slug'] ?? null)
            ? Str::slug($data['slug'])
            : Str::slug($data['name']);

        if ($category) {
            $exists = Category::query()
                ->where('slug', $slug)
                ->where('id', '!=', $category->id)
                ->exists();
            if ($exists) {
                $slug .= '-'.Str::lower(Str::random(4));
            }
        } else {
            $slug .= '-'.Str::lower(Str::random(4));
        }

        $meta = is_array($category?->meta) ? $category->meta : [];
        $meta['additional_description'] = $data['additional_description'] ?? null;
        $meta['meta_title'] = $data['meta_title'] ?? null;
        $meta['meta_description'] = $data['meta_description'] ?? null;
        $meta['redirect_type'] = $data['redirect_type'] ?? '301';
        $meta['redirect_category_id'] = $data['redirect_category_id'] ?? null;
        $meta['group_ids'] = array_values(array_map('intval', $data['group_ids'] ?? []));

        return [
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
            'description' => $data['description'] ?? null,
            'position' => $data['position'] ?? 0,
            'active' => $request->boolean('active', true),
            'slug' => $slug,
            'meta' => $meta,
        ];
    }

    protected function updateCategoryImages(Request $request, Category $category): void
    {
        if ($request->boolean('remove_cover_image') && $category->cover_image_path) {
            Storage::disk('public')->delete($category->cover_image_path);
            $category->update(['cover_image_path' => null]);
        }

        if ($request->boolean('remove_thumbnail') && $category->thumbnail_path) {
            Storage::disk('public')->delete($category->thumbnail_path);
            $category->update(['thumbnail_path' => null]);
        }

        if ($request->hasFile('cover_image')) {
            if ($category->cover_image_path) {
                Storage::disk('public')->delete($category->cover_image_path);
            }
            $path = $request->file('cover_image')->store('categories', 'public');
            $category->update(['cover_image_path' => $path]);
        }

        if ($request->hasFile('thumbnail')) {
            if ($category->thumbnail_path) {
                Storage::disk('public')->delete($category->thumbnail_path);
            }
            $path = $request->file('thumbnail')->store('categories', 'public');
            $category->update(['thumbnail_path' => $path]);
        }
    }

    protected function deleteCategoryImages(Category $category): void
    {
        if ($category->cover_image_path) {
            Storage::disk('public')->delete($category->cover_image_path);
        }
        if ($category->thumbnail_path) {
            Storage::disk('public')->delete($category->thumbnail_path);
        }
    }

    public function brands(Request $request): View
    {
        $query = Brand::query()
            ->withCount(['products', 'addresses'])
            ->orderBy('name');

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->string('name').'%');
        }
        if ($request->filled('active')) {
            $query->where('active', $request->string('active') === '1');
        }

        $addressesQuery = BrandAddress::query()->with('brand')->latest();
        if ($request->filled('address_id')) {
            $addressesQuery->where('id', $request->integer('address_id'));
        }
        if ($request->filled('address_brand')) {
            $addressesQuery->whereHas('brand', fn ($q) => $q->where('name', 'like', '%'.$request->string('address_brand').'%'));
        }
        if ($request->filled('address_city')) {
            $addressesQuery->where('city', 'like', '%'.$request->string('address_city').'%');
        }
        if ($request->filled('address_postcode')) {
            $addressesQuery->where('postcode', 'like', '%'.$request->string('address_postcode').'%');
        }
        if ($request->filled('address_country')) {
            $addressesQuery->where('country', 'like', '%'.$request->string('address_country').'%');
        }

        return view('admin.catalog.brands', [
            'brands' => $query->paginate(20)->withQueryString(),
            'addresses' => $addressesQuery->paginate(10, ['*'], 'addresses_page')->withQueryString(),
        ]);
    }

    public function createBrand(): View
    {
        return view('admin.catalog.brand-form', [
            'brand' => new Brand(['active' => true]),
            'mode' => 'create',
        ]);
    }

    public function storeBrand(Request $request): RedirectResponse
    {
        $data = $this->validatedBrand($request);

        $brand = DB::transaction(function () use ($request, $data) {
            $brand = Brand::query()->create([
                ...$data,
                'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
                'active' => $request->boolean('active', true),
            ]);
            $this->updateBrandLogo($request, $brand);

            return $brand;
        });

        return redirect()
            ->route('admin.catalog.brands.edit', $brand)
            ->with('success', 'Brand created.');
    }

    public function showBrand(Brand $brand): View
    {
        $brand->loadCount(['products', 'addresses'])->load('addresses');

        return view('admin.catalog.brand-view', [
            'brand' => $brand,
        ]);
    }

    public function editBrand(Brand $brand): View
    {
        return view('admin.catalog.brand-form', [
            'brand' => $brand,
            'mode' => 'edit',
        ]);
    }

    public function updateBrand(Request $request, Brand $brand): RedirectResponse
    {
        $data = $this->validatedBrand($request, $brand);

        DB::transaction(function () use ($request, $brand, $data) {
            $brand->update([
                ...$data,
                'active' => $request->boolean('active'),
            ]);
            $this->updateBrandLogo($request, $brand);
        });

        return redirect()
            ->route('admin.catalog.brands.edit', $brand)
            ->with('success', 'Brand updated.');
    }

    public function toggleBrand(Brand $brand): RedirectResponse
    {
        $brand->update(['active' => ! $brand->active]);

        return back()->with('success', 'Brand updated.');
    }

    public function bulkBrands(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:enable,disable,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:brands,id'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['ids'])));
        $brands = Brand::query()->whereIn('id', $ids)->get();

        if ($data['action'] === 'enable') {
            Brand::query()->whereIn('id', $ids)->update(['active' => true]);

            return back()->with('success', $brands->count().' brand(s) enabled.');
        }

        if ($data['action'] === 'disable') {
            Brand::query()->whereIn('id', $ids)->update(['active' => false]);

            return back()->with('success', $brands->count().' brand(s) disabled.');
        }

        $deleted = 0;
        $skipped = 0;

        foreach ($brands as $brand) {
            if ($brand->products()->exists()) {
                $skipped++;
                continue;
            }
            $this->deleteBrandLogo($brand);
            $brand->delete();
            $deleted++;
        }

        $message = $deleted.' brand(s) deleted.';
        if ($skipped > 0) {
            $message .= ' '.$skipped.' skipped (assigned to products).';
        }

        return back()->with($deleted ? 'success' : 'error', $message);
    }

    public function destroyBrand(Brand $brand): RedirectResponse
    {
        if ($brand->products()->exists()) {
            return back()->with('error', 'Brand is assigned to products. Reassign them first.');
        }

        $this->deleteBrandLogo($brand);
        $brand->delete();

        return redirect()
            ->route('admin.catalog.brands')
            ->with('success', 'Brand deleted.');
    }

    public function createBrandAddress(Request $request): View
    {
        $brandId = $request->filled('brand_id') ? $request->integer('brand_id') : null;

        return view('admin.catalog.brand-address-form', [
            'address' => new BrandAddress(['brand_id' => $brandId]),
            'brands' => Brand::query()->orderBy('name')->get(),
            'mode' => 'create',
        ]);
    }

    public function storeBrandAddress(Request $request): RedirectResponse
    {
        BrandAddress::query()->create($this->validatedBrandAddress($request));

        return redirect()
            ->route('admin.catalog.brands')
            ->with('success', 'Brand address created.');
    }

    public function editBrandAddress(BrandAddress $brandAddress): View
    {
        return view('admin.catalog.brand-address-form', [
            'address' => $brandAddress,
            'brands' => Brand::query()->orderBy('name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function updateBrandAddress(Request $request, BrandAddress $brandAddress): RedirectResponse
    {
        $brandAddress->update($this->validatedBrandAddress($request));

        return redirect()
            ->route('admin.catalog.brands')
            ->with('success', 'Brand address updated.');
    }

    public function destroyBrandAddress(BrandAddress $brandAddress): RedirectResponse
    {
        $brandAddress->delete();

        return back()->with('success', 'Brand address deleted.');
    }

    public function suppliers(Request $request): View
    {
        $query = Supplier::query()->withCount('products')->orderBy('name');

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->string('name').'%');
        }
        if ($request->filled('active')) {
            $query->where('active', $request->string('active') === '1');
        }

        return view('admin.catalog.suppliers', [
            'suppliers' => $query->paginate(20)->withQueryString(),
            'suppliersDisplayEnabled' => (string) Configuration::get('PS_DISPLAY_SUPPLIERS', '0') === '1',
        ]);
    }

    public function createSupplier(): View
    {
        return view('admin.catalog.supplier-form', [
            'supplier' => new Supplier(['active' => true]),
            'mode' => 'create',
        ]);
    }

    public function storeSupplier(Request $request): RedirectResponse
    {
        $supplier = DB::transaction(function () use ($request) {
            $supplier = Supplier::query()->create([
                ...$this->validatedSupplier($request),
                'active' => $request->boolean('active', true),
            ]);
            $this->updateSupplierLogo($request, $supplier);

            return $supplier;
        });

        return redirect()
            ->route('admin.catalog.suppliers.edit', $supplier)
            ->with('success', 'Supplier created.');
    }

    public function showSupplier(Supplier $supplier): View
    {
        $supplier->loadCount('products');

        return view('admin.catalog.supplier-view', [
            'supplier' => $supplier,
        ]);
    }

    public function editSupplier(Supplier $supplier): View
    {
        return view('admin.catalog.supplier-form', [
            'supplier' => $supplier,
            'mode' => 'edit',
        ]);
    }

    public function updateSupplier(Request $request, Supplier $supplier): RedirectResponse
    {
        DB::transaction(function () use ($request, $supplier) {
            $supplier->update([
                ...$this->validatedSupplier($request),
                'active' => $request->boolean('active'),
            ]);
            $this->updateSupplierLogo($request, $supplier);
        });

        return redirect()
            ->route('admin.catalog.suppliers.edit', $supplier)
            ->with('success', 'Supplier updated.');
    }

    public function toggleSupplier(Supplier $supplier): RedirectResponse
    {
        $supplier->update(['active' => ! $supplier->active]);

        return back()->with('success', 'Supplier updated.');
    }

    public function bulkSuppliers(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:enable,disable,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:suppliers,id'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['ids'])));
        $suppliers = Supplier::query()->whereIn('id', $ids)->get();

        if ($data['action'] === 'enable') {
            Supplier::query()->whereIn('id', $ids)->update(['active' => true]);

            return back()->with('success', $suppliers->count().' supplier(s) enabled.');
        }

        if ($data['action'] === 'disable') {
            Supplier::query()->whereIn('id', $ids)->update(['active' => false]);

            return back()->with('success', $suppliers->count().' supplier(s) disabled.');
        }

        $deleted = 0;
        $skipped = 0;

        foreach ($suppliers as $supplier) {
            if ($supplier->products()->exists()) {
                $skipped++;
                continue;
            }
            $this->deleteSupplierLogo($supplier);
            $supplier->delete();
            $deleted++;
        }

        $message = $deleted.' supplier(s) deleted.';
        if ($skipped > 0) {
            $message .= ' '.$skipped.' skipped (assigned to products).';
        }

        return back()->with($deleted ? 'success' : 'error', $message);
    }

    public function destroySupplier(Supplier $supplier): RedirectResponse
    {
        if ($supplier->products()->exists()) {
            return back()->with('error', 'Supplier is assigned to products. Reassign them first.');
        }

        $this->deleteSupplierLogo($supplier);
        $supplier->delete();

        return redirect()
            ->route('admin.catalog.suppliers')
            ->with('success', 'Supplier deleted.');
    }

    public function stock(Request $request): View
    {
        $lowStock = (float) Configuration::get('PS_PRODUCT_LOW_STOCK', '5');
        $tab = $request->string('tab')->toString() === 'movements' ? 'movements' : 'stock';

        if ($tab === 'movements') {
            $movementsQuery = InventoryMovement::query()
                ->with(['product.supplier', 'employee'])
                ->latest();

            if ($request->filled('q')) {
                $q = '%'.$request->string('q').'%';
                $movementsQuery->where(function ($query) use ($q) {
                    $query->where('reference', 'like', $q)
                        ->orWhere('notes', 'like', $q)
                        ->orWhere('type', 'like', $q)
                        ->orWhereHas('product', function ($productQuery) use ($q) {
                            $productQuery->where('name', 'like', $q)
                                ->orWhere('sku', 'like', $q)
                                ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', $q));
                        });
                });
            }

            return view('admin.catalog.stock', [
                'tab' => 'movements',
                'products' => null,
                'movements' => $movementsQuery->paginate(30)->withQueryString(),
                'lowStock' => $lowStock,
                'lowFirst' => false,
            ]);
        }

        $reservedStatuses = OrderStatus::query()
            ->where('is_delivered', false)
            ->where('is_cancelled', false)
            ->pluck('code')
            ->all();

        $reservedSub = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->when($reservedStatuses !== [], fn ($q) => $q->whereIn('orders.status', $reservedStatuses))
            ->when($reservedStatuses === [], fn ($q) => $q->whereRaw('1 = 0'))
            ->select('order_items.product_id', DB::raw('SUM(order_items.quantity) as reserved_qty'))
            ->groupBy('order_items.product_id');

        $query = Product::query()
            ->with(['supplier'])
            ->leftJoinSub($reservedSub, 'reserved', 'reserved.product_id', '=', 'products.id')
            ->select('products.*', DB::raw('COALESCE(reserved.reserved_qty, 0) as reserved_quantity'))
            ->where('products.track_inventory', true);

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($inner) use ($q) {
                $inner->where('products.name', 'like', $q)
                    ->orWhere('products.sku', 'like', $q)
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', $q));
            });
        }

        if ($request->filled('supplier_id')) {
            $query->where('products.supplier_id', $request->integer('supplier_id'));
        }

        if ($request->filled('active')) {
            $query->where('products.active', $request->string('active') === '1');
        }

        $lowFirst = $request->boolean('low_first');
        if ($lowFirst || $request->string('filter') === 'low') {
            if ($request->string('filter') === 'low') {
                $query->where('products.quantity', '<=', $lowStock);
            }
            $query->orderByRaw('CASE WHEN products.quantity <= ? THEN 0 ELSE 1 END', [$lowStock])
                ->orderBy('products.quantity');
        } else {
            $sort = $request->string('sort')->toString();
            $dir = $request->string('dir')->toString() === 'desc' ? 'desc' : 'asc';
            $query->orderBy(match ($sort) {
                'name' => 'products.name',
                'sku' => 'products.sku',
                'physical' => 'products.quantity',
                'id' => 'products.id',
                default => 'products.id',
            }, $dir);
        }

        return view('admin.catalog.stock', [
            'tab' => 'stock',
            'products' => $query->paginate(30)->withQueryString(),
            'movements' => null,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'lowStock' => $lowStock,
            'lowFirst' => $lowFirst,
        ]);
    }

    public function adjustStock(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['nullable', 'in:in,out,adjustment,delta'],
            'quantity' => ['required', 'numeric'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::query()->findOrFail($data['product_id']);

        if (! $product->track_inventory) {
            return back()->with('error', 'This product does not track inventory.');
        }

        $type = $data['type'] ?? 'delta';
        $qty = (float) $data['quantity'];

        if ($type === 'delta' && $qty == 0.0) {
            return back()->with('error', 'Enter a non-zero quantity change.');
        }

        DB::transaction(function () use ($data, $product, $type, $qty) {
            [$movementType, $movementQty, $newQuantity] = match ($type) {
                'in' => ['in', $qty, (float) $product->quantity + $qty],
                'out' => ['out', -$qty, (float) $product->quantity - $qty],
                'adjustment' => ['adjustment', $qty - (float) $product->quantity, $qty],
                default => ['adjustment', $qty, (float) $product->quantity + $qty],
            };

            $product->update(['quantity' => max(0, $newQuantity)]);

            InventoryMovement::query()->create([
                'product_id' => $product->id,
                'type' => $movementType,
                'quantity' => $movementQty,
                'reference' => 'ADJ-'.strtoupper(Str::random(6)),
                'notes' => $data['notes'] ?? 'Employee Edition',
                'employee_id' => auth()->user()?->employee?->id,
            ]);
        });

        return back()->with('success', 'Stock updated.');
    }

    public function bulkAdjustStock(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $delta = (float) $data['quantity'];
        if ($delta == 0.0) {
            return back()->with('error', 'Enter a non-zero quantity change.');
        }

        $ids = array_values(array_unique(array_map('intval', $data['ids'])));
        $updated = 0;

        DB::transaction(function () use ($ids, $delta, $data, &$updated) {
            $products = Product::query()
                ->whereIn('id', $ids)
                ->where('track_inventory', true)
                ->get();

            foreach ($products as $product) {
                $newQuantity = max(0, (float) $product->quantity + $delta);
                $product->update(['quantity' => $newQuantity]);

                InventoryMovement::query()->create([
                    'product_id' => $product->id,
                    'type' => 'adjustment',
                    'quantity' => $delta,
                    'reference' => 'BULK-'.strtoupper(Str::random(6)),
                    'notes' => $data['notes'] ?? 'Bulk edit quantity',
                    'employee_id' => auth()->user()?->employee?->id,
                ]);
                $updated++;
            }
        });

        return back()->with('success', $updated.' product(s) stock updated.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedProduct(Request $request, ?Product $product = null): array
    {
        $skuRequired = (string) Configuration::get('PS_PRODUCT_SKU_REQUIRED', '1') === '1';
        $skuRule = [$skuRequired ? 'required' : 'nullable', 'string', 'max:64', 'unique:products,sku'];
        if ($product) {
            $skuRule = [$skuRequired ? 'required' : 'nullable', 'string', 'max:64', 'unique:products,sku,'.$product->id];
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'sku' => $skuRule,
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'supplier_sku' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:10000'],
            'summary' => ['nullable', 'string', 'max:800'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:512'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'depth' => ['nullable', 'numeric', 'min:0'],
            'type' => ['required', Rule::in(array_keys(Product::typeOptions()))],
            'quantity' => ['nullable', 'numeric'],
            'track_inventory' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
            'pack_items' => ['nullable', 'array'],
            'pack_items.*.item_product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->whereIn('type', [Product::TYPE_PRODUCT, Product::TYPE_SERVICE]),
            ],
            'pack_items.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
            'product_features' => ['nullable', 'array'],
            'product_features.*.feature_id' => ['nullable', 'integer', 'exists:features,id'],
            'product_features.*.feature_value_id' => ['nullable', 'integer', 'exists:feature_values,id'],
            'combinations' => ['nullable', 'array'],
            'combinations.*.reference' => ['nullable', 'string', 'max:64'],
            'combinations.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'combinations.*.price_impact' => ['nullable', 'numeric'],
            'combinations.*.active' => ['nullable', 'boolean'],
            'combinations.*.attribute_value_ids' => ['nullable', 'array'],
            'combinations.*.attribute_value_ids.*' => ['integer', 'exists:attribute_values,id'],
            'virtual_file' => ['nullable', 'file', 'max:51200'],
            'remove_virtual_file' => ['nullable', 'boolean'],
            'download_limit' => ['nullable', 'integer', 'min:0'],
            'download_expiry_days' => ['nullable', 'integer', 'min:0'],
            'download_expires_at' => ['nullable', 'date'],
        ]);

        $data['track_inventory'] = $request->boolean('track_inventory');
        $data['active'] = $request->boolean('active');
        $data['sku'] = filled($data['sku'] ?? null)
            ? $data['sku']
            : 'PRD-'.strtoupper(Str::random(8));
        $data['quantity'] = $data['quantity'] ?? 0;
        $data['category_id'] = $data['category_id'] ?: null;
        $data['brand_id'] = $data['brand_id'] ?: null;
        $data['supplier_id'] = $data['supplier_id'] ?: null;
        $data['download_limit'] = $data['download_limit'] ?? null;
        $data['download_expiry_days'] = $data['download_expiry_days'] ?? null;
        $data['download_expires_at'] = $data['download_expires_at'] ?? null;
        $data['weight'] = $data['weight'] ?? 0;
        $data['width'] = $data['width'] ?? 0;
        $data['height'] = $data['height'] ?? 0;
        $data['depth'] = $data['depth'] ?? 0;

        $meta = is_array($product?->meta) ? $product->meta : [];
        $meta['summary'] = $data['summary'] ?? null;
        $meta['meta_title'] = $data['meta_title'] ?? null;
        $meta['meta_description'] = $data['meta_description'] ?? null;
        $data['meta'] = $meta;
        unset($data['summary'], $data['meta_title'], $data['meta_description']);

        if ($data['type'] === Product::TYPE_VIRTUAL) {
            $data['track_inventory'] = false;
            $data['quantity'] = 0;
            $data['weight'] = 0;
            $data['width'] = 0;
            $data['height'] = 0;
            $data['depth'] = 0;
        }

        if ($data['type'] === Product::TYPE_PACK) {
            $packItems = collect($data['pack_items'] ?? [])
                ->filter(fn ($item) => ! empty($item['item_product_id']))
                ->map(function (array $item) use ($product) {
                    $itemId = (int) $item['item_product_id'];
                    if ($product && $itemId === (int) $product->id) {
                        return null;
                    }

                    return [
                        'item_product_id' => $itemId,
                        'quantity' => (float) ($item['quantity'] ?? 1),
                    ];
                })
                ->filter()
                ->unique('item_product_id')
                ->values();

            if ($packItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'pack_items' => 'A pack must contain at least one product.',
                ]);
            }

            $data['pack_items'] = $packItems->all();
        } else {
            $data['pack_items'] = [];
            $data['download_limit'] = $data['type'] === Product::TYPE_VIRTUAL ? $data['download_limit'] : null;
            $data['download_expiry_days'] = $data['type'] === Product::TYPE_VIRTUAL ? $data['download_expiry_days'] : null;
            $data['download_expires_at'] = $data['type'] === Product::TYPE_VIRTUAL ? $data['download_expires_at'] : null;
        }

        $data['product_features'] = $this->normalizeProductFeatures($data['product_features'] ?? []);
        $data['combinations'] = $this->normalizeProductCombinations($data['combinations'] ?? []);

        unset($data['image'], $data['remove_image']);

        return $data;
    }

    /**
     * @return Collection<int, Product>
     */
    protected function packComponentCandidates(?int $excludeId = null)
    {
        return Product::query()
            ->whereIn('type', [Product::TYPE_PRODUCT, Product::TYPE_SERVICE])
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'quantity', 'track_inventory', 'type']);
    }

    /**
     * @param  list<array{item_product_id:int, quantity:float}>  $packItems
     */
    protected function syncPackItems(Product $product, array $packItems): void
    {
        if ($product->type !== Product::TYPE_PACK) {
            $product->packItems()->delete();

            return;
        }

        $product->packItems()->delete();

        foreach ($packItems as $item) {
            ProductPackItem::query()->create([
                'pack_product_id' => $product->id,
                'item_product_id' => $item['item_product_id'],
                'quantity' => $item['quantity'],
            ]);
        }
    }

    /**
     * @return Collection<int, Feature>
     */
    protected function featuresCatalog()
    {
        return Feature::query()
            ->with(['values' => fn ($q) => $q->orderBy('position')->orderBy('value')])
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, AttributeGroup>
     */
    protected function attributeGroupsCatalog()
    {
        return AttributeGroup::query()
            ->with(['values' => fn ($q) => $q->orderBy('position')->orderBy('name')])
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  list<array{feature_id?:mixed, feature_value_id?:mixed}>  $rows
     * @return list<array{feature_id:int, feature_value_id:int}>
     */
    protected function normalizeProductFeatures(array $rows): array
    {
        $normalized = collect($rows)
            ->filter(fn ($row) => ! empty($row['feature_id']) && ! empty($row['feature_value_id']))
            ->map(fn (array $row) => [
                'feature_id' => (int) $row['feature_id'],
                'feature_value_id' => (int) $row['feature_value_id'],
            ])
            ->unique('feature_id')
            ->values();

        if ($normalized->isEmpty()) {
            return [];
        }

        $valueMap = FeatureValue::query()
            ->whereIn('id', $normalized->pluck('feature_value_id'))
            ->pluck('feature_id', 'id');

        foreach ($normalized as $row) {
            if ((int) ($valueMap[$row['feature_value_id']] ?? 0) !== $row['feature_id']) {
                throw ValidationException::withMessages([
                    'product_features' => 'Each feature value must belong to the selected feature.',
                ]);
            }
        }

        return $normalized->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{reference:?string, quantity:float, price_impact:float, active:bool, attribute_value_ids:list<int>}>
     */
    protected function normalizeProductCombinations(array $rows): array
    {
        $normalized = collect($rows)
            ->map(function (array $row) {
                $valueIds = collect($row['attribute_value_ids'] ?? [])
                    ->filter(fn ($id) => filled($id))
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                if ($valueIds === [] && ! filled($row['reference'] ?? null)) {
                    return null;
                }

                return [
                    'reference' => filled($row['reference'] ?? null) ? (string) $row['reference'] : null,
                    'quantity' => (float) ($row['quantity'] ?? 0),
                    'price_impact' => (float) ($row['price_impact'] ?? 0),
                    'active' => filter_var($row['active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'attribute_value_ids' => $valueIds,
                ];
            })
            ->filter()
            ->values();

        foreach ($normalized as $index => $row) {
            if ($row['attribute_value_ids'] === []) {
                throw ValidationException::withMessages([
                    'combinations' => 'Each combination needs at least one attribute value.',
                ]);
            }

            $groups = AttributeValue::query()
                ->whereIn('id', $row['attribute_value_ids'])
                ->pluck('attribute_group_id');

            if ($groups->count() !== $groups->unique()->count()) {
                throw ValidationException::withMessages([
                    'combinations' => 'Combination #'.($index + 1).' cannot use two values from the same attribute.',
                ]);
            }
        }

        return $normalized->all();
    }

    /**
     * @param  list<array{feature_id:int, feature_value_id:int}>  $rows
     */
    protected function syncProductFeatures(Product $product, array $rows): void
    {
        $sync = [];
        foreach ($rows as $row) {
            $sync[$row['feature_id']] = ['feature_value_id' => $row['feature_value_id']];
        }

        $product->features()->sync($sync);
    }

    /**
     * @param  list<array{reference:?string, quantity:float, price_impact:float, active:bool, attribute_value_ids:list<int>}>  $rows
     */
    protected function syncProductCombinations(Product $product, array $rows): void
    {
        $product->combinations()->each(function (ProductCombination $combination) {
            $combination->attributeValues()->detach();
            $combination->delete();
        });

        foreach ($rows as $position => $row) {
            $combination = ProductCombination::query()->create([
                'product_id' => $product->id,
                'reference' => $row['reference'],
                'quantity' => $row['quantity'],
                'price_impact' => $row['price_impact'],
                'active' => $row['active'],
                'position' => $position,
            ]);

            $combination->attributeValues()->sync($row['attribute_value_ids']);
        }
    }

    protected function updateProductImage(Request $request, Product $product): void
    {
        if ($request->boolean('remove_image') && $product->image_path) {
            Storage::disk('public')->delete($product->image_path);
            $product->update(['image_path' => null]);
        }

        if (! $request->hasFile('image')) {
            return;
        }

        $path = $request->file('image')->store('products', 'public');

        if (! $path) {
            return;
        }

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->update(['image_path' => $path]);
    }

    protected function updateVirtualFile(Request $request, Product $product): void
    {
        if ($product->type !== Product::TYPE_VIRTUAL) {
            return;
        }

        if ($request->boolean('remove_virtual_file') && $product->virtual_file_path) {
            Storage::disk('local')->delete($product->virtual_file_path);
            $product->update([
                'virtual_file_path' => null,
                'virtual_file_name' => null,
            ]);
        }

        if (! $request->hasFile('virtual_file')) {
            return;
        }

        $file = $request->file('virtual_file');
        $path = $file->store('virtual-products', 'local');

        if (! $path) {
            return;
        }

        if ($product->virtual_file_path) {
            Storage::disk('local')->delete($product->virtual_file_path);
        }

        $product->update([
            'virtual_file_path' => $path,
            'virtual_file_name' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedBrand(Request $request, ?Brand $brand = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:brands,name'.($brand ? ','.$brand->id : '')],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'long_description' => ['nullable', 'string', 'max:50000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'active' => ['nullable', 'boolean'],
        ]);

        unset($data['logo'], $data['active']);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedBrandAddress(Request $request): array
    {
        $locations = app(CountryStateData::class);

        return $request->validate([
            'brand_id' => ['required', 'exists:brands,id'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'address1' => ['nullable', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'postcode' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => $locations->stateRules(),
            'country' => $locations->countryRules(),
            'phone' => ['nullable', 'string', 'max:50'],
        ]);
    }

    protected function updateBrandLogo(Request $request, Brand $brand): void
    {
        if ($request->boolean('remove_logo') && $brand->logo_path) {
            Storage::disk('public')->delete($brand->logo_path);
            $brand->update(['logo_path' => null]);
        }

        if ($request->hasFile('logo')) {
            if ($brand->logo_path) {
                Storage::disk('public')->delete($brand->logo_path);
            }
            $path = $request->file('logo')->store('brands', 'public');
            $brand->update(['logo_path' => $path]);
        }
    }

    protected function deleteBrandLogo(Brand $brand): void
    {
        if ($brand->logo_path) {
            Storage::disk('public')->delete($brand->logo_path);
        }
    }

    protected function updateSupplierLogo(Request $request, Supplier $supplier): void
    {
        if ($request->boolean('remove_logo') && $supplier->logo_path) {
            Storage::disk('public')->delete($supplier->logo_path);
            $supplier->update(['logo_path' => null]);
        }

        if ($request->hasFile('logo')) {
            if ($supplier->logo_path) {
                Storage::disk('public')->delete($supplier->logo_path);
            }
            $path = $request->file('logo')->store('suppliers', 'public');
            $supplier->update(['logo_path' => $path]);
        }
    }

    protected function deleteSupplierLogo(Supplier $supplier): void
    {
        if ($supplier->logo_path) {
            Storage::disk('public')->delete($supplier->logo_path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedSupplier(Request $request): array
    {
        $locations = app(CountryStateData::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:50000'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile_phone' => ['nullable', 'string', 'max:50'],
            'tax_number' => ['nullable', 'string', 'max:80'],
            'dni' => ['nullable', 'string', 'max:80'],
            'address' => ['required', 'string', 'max:500'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => $locations->stateRules(),
            'postcode' => ['nullable', 'string', 'max:30'],
            'country' => $locations->countryRules(true),
            'website' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'active' => ['nullable', 'boolean'],
        ]);

        unset($data['logo'], $data['active']);

        return $data;
    }
}
