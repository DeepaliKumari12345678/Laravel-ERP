<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\WebserviceKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WebserviceApiController extends Controller
{
    public function resources(Request $request): JsonResponse
    {
        if ($request->isMethod('HEAD')) {
            return response()->json(null, 200);
        }

        /** @var WebserviceKey $webserviceKey */
        $webserviceKey = $request->attributes->get('webservice_key');
        $baseUrl = url('/api/webservice');

        return response()->json([
            'webservice' => [
                'api_version' => config('erp.version'),
                'shop_name' => shop_name(),
                'base_url' => $baseUrl,
                'usage' => [
                    'list' => 'GET /api/webservice/{resource}?ws_key=KEY',
                    'get' => 'GET /api/webservice/{resource}/{id}?ws_key=KEY',
                    'create' => 'POST /api/webservice/{resource}?ws_key=KEY  (JSON body)',
                    'update' => 'PUT|PATCH /api/webservice/{resource}/{id}?ws_key=KEY  (JSON body)',
                    'delete' => 'DELETE /api/webservice/{resource}/{id}?ws_key=KEY',
                    'note' => 'Use /products/1 — not /products?1',
                ],
                'resources' => $webserviceKey->resourceIndex($baseUrl),
            ],
        ]);
    }

    public function collection(Request $request, string $resource): JsonResponse
    {
        if ($request->isMethod('HEAD')) {
            return response()->json(null, 200);
        }

        if (! $this->known($resource)) {
            return $this->error(404, 'Unknown resource.');
        }

        if ($schema = $this->schemaResponse($request, $resource)) {
            return $schema;
        }

        // PrestaShop-style: /products?id=1
        if ($request->isMethod('GET') && $request->filled('id')) {
            return $this->show($resource, (int) $request->query('id'));
        }

        return match (strtoupper($request->method())) {
            'GET' => $this->index($resource),
            'POST' => $this->store($request, $resource),
            default => $this->error(405, 'Method not allowed on this URL. Use /{resource}/{id} for PUT/PATCH/DELETE.'),
        };
    }

    public function item(Request $request, string $resource, int $id): JsonResponse
    {
        if ($request->isMethod('HEAD')) {
            return response()->json(null, 200);
        }

        if (! $this->known($resource)) {
            return $this->error(404, 'Unknown resource.');
        }

        return match (strtoupper($request->method())) {
            'GET' => $this->show($resource, $id),
            'PUT', 'PATCH' => $this->update($request, $resource, $id),
            'DELETE' => $this->destroy($resource, $id),
            default => $this->error(405, 'Method not allowed.'),
        };
    }

    protected function index(string $resource): JsonResponse
    {
        $payload = match ($resource) {
            'products' => Product::query()->latest('id')->limit(100)->get($this->listColumns($resource)),
            'categories' => Category::query()->orderBy('position')->limit(100)->get($this->listColumns($resource)),
            'brands' => Brand::query()->orderBy('name')->limit(100)->get($this->listColumns($resource)),
            'suppliers' => Supplier::query()->orderBy('name')->limit(100)->get($this->listColumns($resource)),
            'customers' => Customer::query()->latest('id')->limit(100)->get($this->listColumns($resource)),
            'orders' => Order::query()->latest('id')->limit(100)->get($this->listColumns($resource)),
            'addresses' => CustomerAddress::query()->latest('id')->limit(100)->get(),
            'stock' => Product::query()->where('track_inventory', true)->orderBy('quantity')->limit(100)->get(['id', 'name', 'sku', 'quantity']),
            default => null,
        };

        return response()->json([
            'resource' => $resource,
            'href' => url('/api/webservice/'.$resource),
            'count' => $payload?->count() ?? 0,
            'data' => $payload,
        ]);
    }

    protected function show(string $resource, int $id): JsonResponse
    {
        $model = $this->find($resource, $id);
        if (! $model) {
            return $this->error(404, ucfirst($resource)." #{$id} not found.");
        }

        return response()->json([
            'resource' => $resource,
            'href' => url('/api/webservice/'.$resource.'/'.$id),
            'data' => $model,
        ]);
    }

    protected function store(Request $request, string $resource): JsonResponse
    {
        if (in_array($resource, ['orders', 'stock'], true)) {
            return $this->error(405, "Creating {$resource} via API is not supported. Use the admin panel.");
        }

        try {
            $data = $this->validated($request, $resource);
            $model = $this->query($resource)->create($data);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => collect($e->errors())->flatten()->map(fn ($m) => ['code' => 422, 'message' => $m])->values(),
                'hint' => 'Send Body type JSON with curly braces, e.g. {"name":"Product","price":67,"quantity":23}. Header Content-Type: application/json. Key needs POST permission on products.',
            ], 422);
        }

        return response()->json([
            'resource' => $resource,
            'href' => url('/api/webservice/'.$resource.'/'.$model->getKey()),
            'message' => ucfirst(rtrim($resource, 's')).' created.',
            'data' => $model->fresh(),
        ], 201);
    }

    protected function update(Request $request, string $resource, int $id): JsonResponse
    {
        if ($resource === 'orders') {
            return $this->error(405, 'Updating orders via API is not supported.');
        }

        $model = $this->find($resource, $id);
        if (! $model) {
            return $this->error(404, ucfirst($resource)." #{$id} not found.");
        }

        try {
            if ($resource === 'stock') {
                $this->mergeJsonBody($request, $resource);
                $data = $request->validate([
                    'quantity' => ['required', 'numeric', 'min:0'],
                ]);
            } else {
                $data = $this->validated($request, $resource, $model);
            }
            $model->update($data);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => collect($e->errors())->flatten()->map(fn ($m) => ['code' => 422, 'message' => $m])->values(),
            ], 422);
        }

        return response()->json([
            'resource' => $resource,
            'href' => url('/api/webservice/'.$resource.'/'.$id),
            'message' => ucfirst(rtrim($resource, 's')).' updated.',
            'data' => $model->fresh(),
        ]);
    }

    protected function destroy(string $resource, int $id): JsonResponse
    {
        if (in_array($resource, ['orders', 'stock'], true)) {
            return $this->error(405, "Deleting {$resource} via API is not supported.");
        }

        $model = $this->find($resource, $id);
        if (! $model) {
            return $this->error(404, ucfirst($resource)." #{$id} not found.");
        }

        $model->delete();

        return response()->json([
            'resource' => $resource,
            'id' => $id,
            'message' => ucfirst(rtrim($resource, 's')).' deleted.',
        ]);
    }

    protected function known(string $resource): bool
    {
        return isset(WebserviceKey::resourceCatalog()[$resource]);
    }

    protected function schemaResponse(Request $request, string $resource): ?JsonResponse
    {
        $schema = strtolower((string) $request->query('schema', ''));
        if ($schema !== 'blank' && $schema !== 'synopsis') {
            return null;
        }

        $catalog = WebserviceKey::resourceCatalog()[$resource];

        return response()->json([
            'resource' => $resource,
            'schema' => $schema,
            'description' => $catalog['description'],
            'fields' => array_fill_keys($catalog['fields'], $schema === 'blank' ? '' : [
                'required' => false,
                'type' => 'string',
            ]),
        ]);
    }

    protected function find(string $resource, int $id): ?Model
    {
        if ($resource === 'stock') {
            return Product::query()->where('track_inventory', true)->find($id);
        }

        return $this->query($resource)->find($id);
    }

    protected function query(string $resource)
    {
        return match ($resource) {
            'products', 'stock' => Product::query(),
            'categories' => Category::query(),
            'brands' => Brand::query(),
            'suppliers' => Supplier::query(),
            'customers' => Customer::query(),
            'orders' => Order::query(),
            'addresses' => CustomerAddress::query(),
            default => Product::query(),
        };
    }

    /**
     * @return list<string>
     */
    protected function listColumns(string $resource): array
    {
        return WebserviceKey::resourceCatalog()[$resource]['fields'] ?? ['id'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, string $resource, ?Model $existing = null): array
    {
        $input = $this->payload($request, $resource);

        $rules = match ($resource) {
            'products' => [
                'name' => [$existing ? 'sometimes' : 'required', 'string', 'max:255'],
                'sku' => ['nullable', 'string', 'max:100'],
                'price' => ['nullable', 'numeric', 'min:0'],
                'quantity' => ['nullable', 'numeric', 'min:0'],
                'active' => ['nullable', 'boolean'],
                'description' => ['nullable', 'string'],
                'category_id' => ['nullable', 'integer', 'exists:categories,id'],
                'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
                'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
                'type' => ['nullable', 'string', 'in:product,service,pack,virtual'],
                'track_inventory' => ['nullable', 'boolean'],
            ],
            'categories' => [
                'name' => [$existing ? 'sometimes' : 'required', 'string', 'max:255'],
                'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
                'description' => ['nullable', 'string'],
                'active' => ['nullable', 'boolean'],
                'position' => ['nullable', 'integer', 'min:0'],
            ],
            'brands' => [
                'name' => [$existing ? 'sometimes' : 'required', 'string', 'max:255'],
                'website' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'active' => ['nullable', 'boolean'],
            ],
            'suppliers' => [
                'name' => [$existing ? 'sometimes' : 'required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:150'],
                'phone' => ['nullable', 'string', 'max:50'],
                'active' => ['nullable', 'boolean'],
            ],
            'customers' => [
                'first_name' => [$existing ? 'sometimes' : 'required', 'string', 'max:100'],
                'last_name' => ['nullable', 'string', 'max:100'],
                'email' => [
                    $existing ? 'sometimes' : 'required',
                    'email',
                    'max:150',
                    Rule::unique('customers', 'email')->ignore($existing?->getKey()),
                ],
                'phone' => ['nullable', 'string', 'max:50'],
                'active' => ['nullable', 'boolean'],
            ],
            'addresses' => [
                'customer_id' => [$existing ? 'sometimes' : 'required', 'integer', 'exists:customers,id'],
                'alias' => ['nullable', 'string', 'max:100'],
                'first_name' => ['nullable', 'string', 'max:100'],
                'last_name' => ['nullable', 'string', 'max:100'],
                'address1' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:100'],
                'postcode' => ['nullable', 'string', 'max:30'],
                'country' => ['nullable', 'string', 'max:100'],
                'phone' => ['nullable', 'string', 'max:50'],
            ],
            default => throw ValidationException::withMessages(['resource' => 'Unsupported resource.']),
        };

        $data = validator($input, $rules)->validate();

        if (isset($data['name']) && in_array($resource, ['products', 'categories', 'brands'], true) && ! $existing) {
            $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(4));
        }

        if ($resource === 'products' && ! $existing) {
            $data['type'] = $data['type'] ?? Product::TYPE_PRODUCT;
            $data['price'] = $data['price'] ?? 0;
            $data['quantity'] = $data['quantity'] ?? 0;
            $data['active'] = $data['active'] ?? true;
            $data['track_inventory'] = $data['track_inventory'] ?? true;
            $data['sku'] = filled($data['sku'] ?? null)
                ? $data['sku']
                : strtoupper(Str::slug(Str::limit($data['name'], 20, ''), '-').'-'.Str::upper(Str::random(4)));
        }

        if (array_key_exists('active', $data)) {
            $data['active'] = (bool) $data['active'];
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(Request $request, string $resource): array
    {
        $payload = $request->request->all() + $request->query->all();
        $raw = trim((string) $request->getContent());

        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw ValidationException::withMessages([
                    'body' => 'Invalid JSON body. Use: {"name":"Product name","price":67,"quantity":23}',
                ]);
            }
            if (is_array($decoded)) {
                $payload = array_merge($payload, $decoded);
            }
        }

        $singular = match ($resource) {
            'addresses' => 'address',
            'categories' => 'category',
            'products' => 'product',
            'orders' => 'order',
            'customers' => 'customer',
            'suppliers' => 'supplier',
            'brands' => 'brand',
            default => rtrim($resource, 's'),
        };

        if (isset($payload[$singular]) && is_array($payload[$singular])) {
            $payload = $payload[$singular];
        } elseif (isset($payload[$resource]) && is_array($payload[$resource])) {
            $payload = $payload[$resource];
        }

        unset($payload['ws_key']);

        return is_array($payload) ? $payload : [];
    }

    protected function mergeJsonBody(Request $request, string $resource): void
    {
        $request->merge($this->payload($request, $resource));
    }

    protected function error(int $code, string $message): JsonResponse
    {
        return response()->json([
            'errors' => [['code' => $code, 'message' => $message]],
        ], $code);
    }
}
