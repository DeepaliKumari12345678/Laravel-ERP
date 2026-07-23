<?php

use App\Core\Configuration\Configuration;

if (! function_exists('configuration')) {
    function configuration(string $key, mixed $default = null): mixed
    {
        return Configuration::get($key, $default);
    }
}

if (! function_exists('shop_logo_url')) {
    function shop_logo_url(): ?string
    {
        $path = configuration('PS_SHOP_LOGO');

        return is_string($path) && $path !== '' ? asset('storage/'.$path) : null;
    }
}

if (! function_exists('shop_name')) {
    function shop_name(): string
    {
        return (string) configuration('PS_SHOP_NAME', config('erp.name'));
    }
}

if (! function_exists('shop_currency')) {
    function shop_currency(): string
    {
        return (string) config('erp.currency', 'INR');
    }
}
