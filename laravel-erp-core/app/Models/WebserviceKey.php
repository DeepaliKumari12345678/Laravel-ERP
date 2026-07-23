<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebserviceKey extends Model
{
    protected $fillable = [
        'key',
        'description',
        'active',
        'permissions',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'permissions' => 'array',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public static function resources(): array
    {
        return array_keys(static::resourceCatalog());
    }

    /**
     * @return array<string, array{description: string, fields: list<string>}>
     */
    public static function resourceCatalog(): array
    {
        return [
            'addresses' => [
                'description' => 'The Customer, Manufacturer and Customer addresses',
                'fields' => ['id', 'customer_id', 'alias', 'firstname', 'lastname', 'address1', 'address2', 'postcode', 'city', 'country', 'state', 'phone', 'active'],
            ],
            'brands' => [
                'description' => 'The product brands / manufacturers',
                'fields' => ['id', 'name', 'active'],
            ],
            'categories' => [
                'description' => 'The product categories',
                'fields' => ['id', 'name', 'parent_id', 'active', 'position'],
            ],
            'customers' => [
                'description' => 'The e-shop customers',
                'fields' => ['id', 'first_name', 'last_name', 'email', 'active'],
            ],
            'orders' => [
                'description' => 'The customers orders',
                'fields' => ['id', 'reference', 'customer_id', 'status', 'total', 'created_at'],
            ],
            'products' => [
                'description' => 'The products',
                'fields' => ['id', 'name', 'sku', 'price', 'quantity', 'active'],
            ],
            'stock' => [
                'description' => 'Available quantities for products with inventory tracking',
                'fields' => ['id', 'name', 'sku', 'quantity'],
            ],
            'suppliers' => [
                'description' => 'The product suppliers',
                'fields' => ['id', 'name', 'active'],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function methods(): array
    {
        return ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD'];
    }

    public function allows(string $resource, string $method): bool
    {
        $permissions = is_array($this->permissions) ? $this->permissions : [];
        $allowed = $permissions[$resource] ?? [];

        return in_array(strtoupper($method), array_map('strtoupper', (array) $allowed), true);
    }

    /**
     * PrestaShop-style resource index for this API key (JSON).
     *
     * @return array<string, array<string, mixed>>
     */
    public function resourceIndex(string $baseUrl): array
    {
        $resources = [];

        foreach (static::resourceCatalog() as $name => $meta) {
            $href = rtrim($baseUrl, '/').'/'.$name;
            $resources[$name] = [
                'href' => $href,
                'description' => $meta['description'],
                'get' => $this->allows($name, 'GET'),
                'post' => $this->allows($name, 'POST'),
                'put' => $this->allows($name, 'PUT'),
                'patch' => $this->allows($name, 'PATCH'),
                'delete' => $this->allows($name, 'DELETE'),
                'head' => $this->allows($name, 'HEAD'),
                'schema' => [
                    'blank' => $href.'?schema=blank',
                    'synopsis' => $href.'?schema=synopsis',
                    'fields' => $meta['fields'],
                ],
            ];
        }

        return $resources;
    }
}
