<?php

namespace App\Core\Configuration;

use App\Models\ErpConfiguration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Configuration
{
    protected const CACHE_KEY = 'erp.configuration';

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function updateValue(string $key, mixed $value): bool
    {
        if (! Schema::hasTable('configurations')) {
            return false;
        }

        $stored = is_array($value) || is_object($value) ? json_encode($value) : (string) $value;

        ErpConfiguration::query()->updateOrCreate(
            ['name' => $key],
            ['value' => $stored]
        );

        Cache::forget(static::CACHE_KEY);

        return true;
    }

    public static function deleteByName(string $key): bool
    {
        if (! Schema::hasTable('configurations')) {
            return false;
        }

        ErpConfiguration::query()->where('name', $key)->delete();
        Cache::forget(static::CACHE_KEY);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (! Schema::hasTable('configurations')) {
            return [];
        }

        return Cache::rememberForever(static::CACHE_KEY, function () {
            return ErpConfiguration::query()
                ->pluck('value', 'name')
                ->map(function ($value) {
                    if (! is_string($value)) {
                        return $value;
                    }

                    $decoded = json_decode($value, true);

                    return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
                })
                ->all();
        });
    }

    public static function clearCache(): void
    {
        Cache::forget(static::CACHE_KEY);
    }
}
