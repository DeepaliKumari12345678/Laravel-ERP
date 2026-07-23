<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Brand extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'website',
        'description',
        'long_description',
        'logo_path',
        'meta_title',
        'meta_description',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(BrandAddress::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }

    public function getLogoSizeLabelAttribute(): ?string
    {
        if (! $this->logo_path || ! Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        $bytes = Storage::disk('public')->size($this->logo_path);

        if ($bytes < 1024) {
            return $bytes.'B';
        }

        return round($bytes / 1024, 3).'kB';
    }
}
