<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    public const TYPE_PRODUCT = 'product';

    public const TYPE_SERVICE = 'service';

    public const TYPE_PACK = 'pack';

    public const TYPE_VIRTUAL = 'virtual';

    protected $fillable = [
        'category_id',
        'brand_id',
        'supplier_id',
        'sku',
        'supplier_sku',
        'name',
        'slug',
        'description',
        'image_path',
        'virtual_file_path',
        'virtual_file_name',
        'download_limit',
        'download_expiry_days',
        'download_expires_at',
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
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'weight' => 'decimal:3',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'depth' => 'decimal:2',
            'quantity' => 'decimal:2',
            'track_inventory' => 'boolean',
            'active' => 'boolean',
            'meta' => 'array',
            'download_limit' => 'integer',
            'download_expiry_days' => 'integer',
            'download_expires_at' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function packItems(): HasMany
    {
        return $this->hasMany(ProductPackItem::class, 'pack_product_id');
    }

    public function packMemberships(): HasMany
    {
        return $this->hasMany(ProductPackItem::class, 'item_product_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'feature_product')
            ->withPivot('feature_value_id')
            ->withTimestamps()
            ->orderBy('features.position')
            ->orderBy('features.name');
    }

    public function featureValues(): BelongsToMany
    {
        return $this->belongsToMany(FeatureValue::class, 'feature_product')
            ->withPivot('feature_id')
            ->withTimestamps();
    }

    public function combinations(): HasMany
    {
        return $this->hasMany(ProductCombination::class)->orderBy('position')->orderBy('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    public function isPack(): bool
    {
        return $this->type === self::TYPE_PACK;
    }

    public function isVirtual(): bool
    {
        return $this->type === self::TYPE_VIRTUAL;
    }

    public function hasVirtualFile(): bool
    {
        return filled($this->virtual_file_path);
    }

    public function deleteVirtualFile(): void
    {
        if ($this->virtual_file_path) {
            Storage::disk('local')->delete($this->virtual_file_path);
        }

        $this->forceFill([
            'virtual_file_path' => null,
            'virtual_file_name' => null,
        ])->save();
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_PRODUCT => 'Standard product',
            self::TYPE_SERVICE => 'Service',
            self::TYPE_PACK => 'Pack of products',
            self::TYPE_VIRTUAL => 'Virtual product',
        ];
    }
}
