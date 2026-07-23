<?php

namespace App\Support;

/**
 * Maps English menu labels (from config) to erp.menu.* translation keys.
 */
class MenuTranslator
{
    /** @var array<string, string> */
    protected static array $map = [
        'Welcome' => 'erp.menu.welcome',
        'Dashboard' => 'erp.menu.dashboard',
        'Sell' => 'erp.menu.sell',
        'Orders' => 'erp.menu.orders',
        'Invoices' => 'erp.menu.invoices',
        'Credit slips' => 'erp.menu.credit_slips',
        'Delivery slips' => 'erp.menu.delivery_slips',
        'Catalog' => 'erp.menu.catalog',
        'Products' => 'erp.menu.products',
        'Categories' => 'erp.menu.categories',
        'Attributes & Features' => 'erp.menu.attributes_features',
        'Brands' => 'erp.menu.brands',
        'Suppliers' => 'erp.menu.suppliers',
        'Brands & Suppliers' => 'erp.menu.brands_suppliers',
        'Stock' => 'erp.menu.stock',
        'Customers' => 'erp.menu.customers',
        'Addresses' => 'erp.menu.addresses',
        'Groups' => 'erp.menu.groups',
        'Titles' => 'erp.menu.titles',
        'Stats' => 'erp.menu.stats',
        'Improve' => 'erp.menu.improve',
        'Shipping' => 'erp.menu.shipping',
        'Carriers' => 'erp.menu.carriers',
        'Preferences' => 'erp.menu.preferences',
        'Payment' => 'erp.menu.payment',
        'Payment methods' => 'erp.menu.payment_methods',
        'Configure' => 'erp.menu.configure',
        'Shop Parameters' => 'erp.menu.shop_parameters',
        'General' => 'erp.menu.general',
        'Order settings' => 'erp.menu.order_settings',
        'Order statuses' => 'erp.menu.order_statuses',
        'Product settings' => 'erp.menu.product_settings',
        'Customer settings' => 'erp.menu.customer_settings',
        'Advanced Parameters' => 'erp.menu.advanced_parameters',
        'Team' => 'erp.menu.team',
        'E-mail' => 'erp.menu.email',
        'Webservice' => 'erp.menu.webservice',
    ];

    public static function label(string $text): string
    {
        $key = self::$map[$text] ?? null;

        return $key ? __($key) : $text;
    }
}
