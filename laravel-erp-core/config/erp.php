<?php

return [
    'name' => env('ERP_NAME', 'Laravel ERP'),
    'version' => '1.0.0',
    'admin_prefix' => env('ERP_ADMIN_PREFIX', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | UI language & currency (fixed for this ERP)
    |--------------------------------------------------------------------------
    */
    'locale' => 'en',
    'currency' => 'INR',

    /*
    |--------------------------------------------------------------------------
    | Admin menu tabs (permission-controlled)
    | Super Admin always sees every tab. Other roles only see assigned tabs.
    |--------------------------------------------------------------------------
    */
    'menu' => [
        [
            'section' => 'Welcome',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'icon' => 'stats',
                    'route' => 'admin.dashboard',
                    'match' => 'admin.dashboard',
                    'permission' => 'tab.dashboard',
                ],
            ],
        ],
        [
            'section' => 'Sell',
            'items' => [
                [
                    'label' => 'Orders',
                    'icon' => 'orders',
                    'permission' => 'tab.orders',
                    'match' => ['admin.orders.*', 'admin.invoices.*', 'admin.credit-slips.*', 'admin.delivery-slips.*'],
                    'children' => [
                        [
                            'label' => 'Orders',
                            'route' => 'admin.orders.index',
                            'match' => 'admin.orders.*',
                            'permission' => 'tab.orders',
                        ],
                        [
                            'label' => 'Invoices',
                            'route' => 'admin.invoices.index',
                            'match' => 'admin.invoices.*',
                            'permission' => 'tab.orders',
                        ],
                        [
                            'label' => 'Credit slips',
                            'route' => 'admin.credit-slips.index',
                            'match' => 'admin.credit-slips.*',
                            'permission' => 'tab.orders',
                        ],
                        [
                            'label' => 'Delivery slips',
                            'route' => 'admin.delivery-slips.index',
                            'match' => 'admin.delivery-slips.*',
                            'permission' => 'tab.orders',
                        ],
                    ],
                ],
                [
                    'label' => 'Catalog',
                    'icon' => 'catalog',
                    'permission' => 'tab.catalog',
                    'match' => 'admin.catalog.*',
                    'children' => [
                        [
                            'label' => 'Products',
                            'route' => 'admin.catalog.products',
                            'match' => 'admin.catalog.products*',
                            'permission' => 'tab.catalog',
                        ],
                        [
                            'label' => 'Categories',
                            'route' => 'admin.catalog.categories',
                            'match' => 'admin.catalog.categories*',
                            'permission' => 'tab.catalog',
                        ],
                        [
                            'label' => 'Attributes & Features',
                            'route' => 'admin.catalog.attributes',
                            'match' => 'admin.catalog.attributes*|admin.catalog.features*',
                            'permission' => 'tab.catalog',
                        ],
                        [
                            'label' => 'Brands & Suppliers',
                            'route' => 'admin.catalog.brands',
                            'match' => 'admin.catalog.brands*|admin.catalog.suppliers*',
                            'permission' => 'tab.catalog',
                        ],
                        [
                            'label' => 'Stock',
                            'route' => 'admin.catalog.stock',
                            'match' => 'admin.catalog.stock*',
                            'permission' => 'tab.catalog',
                        ],
                    ],
                ],
                [
                    'label' => 'Customers',
                    'icon' => 'customers',
                    'permission' => 'tab.customers',
                    'match' => ['admin.customers.*', 'admin.addresses.*', 'admin.customer-groups.*', 'admin.customer-titles.*'],
                    'children' => [
                        [
                            'label' => 'Customers',
                            'route' => 'admin.customers.index',
                            'match' => 'admin.customers.*',
                            'permission' => 'tab.customers',
                        ],
                        [
                            'label' => 'Addresses',
                            'route' => 'admin.addresses.index',
                            'match' => 'admin.addresses.*',
                            'permission' => 'tab.customers',
                        ],
                        [
                            'label' => 'Groups',
                            'route' => 'admin.customer-groups.index',
                            'match' => 'admin.customer-groups.*',
                            'permission' => 'tab.customers',
                        ],
                        [
                            'label' => 'Titles',
                            'route' => 'admin.customer-titles.index',
                            'match' => 'admin.customer-titles.*',
                            'permission' => 'tab.customers',
                        ],
                    ],
                ],
                [
                    'label' => 'Stats',
                    'icon' => 'stats',
                    'route' => 'admin.reports.index',
                    'match' => 'admin.reports.*',
                    'permission' => 'tab.reports',
                ],
            ],
        ],
        [
            'section' => 'Improve',
            'items' => [
                [
                    'label' => 'Shipping',
                    'icon' => 'shipping',
                    'permission' => 'tab.shipping',
                    'match' => 'admin.shipping.*',
                    'children' => [
                        [
                            'label' => 'Carriers',
                            'route' => 'admin.shipping.carriers',
                            'match' => 'admin.shipping.carriers*',
                            'permission' => 'tab.shipping',
                        ],
                        [
                            'label' => 'Preferences',
                            'route' => 'admin.shipping.preferences',
                            'match' => 'admin.shipping.preferences*',
                            'permission' => 'tab.shipping',
                        ],
                    ],
                ],
                [
                    'label' => 'Payment',
                    'icon' => 'payment',
                    'permission' => 'tab.payment',
                    'match' => 'admin.payment.*',
                    'children' => [
                        [
                            'label' => 'Payment methods',
                            'route' => 'admin.payment.methods',
                            'match' => 'admin.payment.*',
                            'permission' => 'tab.payment',
                        ],
                    ],
                ],
            ],
        ],
        [
            'section' => 'Configure',
            'items' => [
                [
                    'label' => 'Shop Parameters',
                    'icon' => 'settings',
                    'permission' => 'tab.settings',
                    'match' => ['admin.settings.*', 'admin.order-statuses.*'],
                    'children' => [
                        [
                            'label' => 'General',
                            'route' => 'admin.settings.group',
                            'route_params' => ['group' => 'shop'],
                            'match' => 'admin.settings.*',
                            'permission' => 'tab.settings',
                        ],
                        [
                            'label' => 'Order settings',
                            'route' => 'admin.settings.group',
                            'route_params' => ['group' => 'orders'],
                            'match' => 'admin.settings.*',
                            'permission' => 'tab.settings',
                        ],
                        [
                            'label' => 'Order statuses',
                            'route' => 'admin.order-statuses.index',
                            'match' => 'admin.order-statuses.*',
                            'permission' => 'tab.settings',
                        ],
                        [
                            'label' => 'Product settings',
                            'route' => 'admin.settings.group',
                            'route_params' => ['group' => 'products'],
                            'match' => 'admin.settings.*',
                            'permission' => 'tab.settings',
                        ],
                        [
                            'label' => 'Customer settings',
                            'route' => 'admin.settings.group',
                            'route_params' => ['group' => 'customers'],
                            'match' => 'admin.settings.*',
                            'permission' => 'tab.settings',
                        ],
                    ],
                ],
                [
                    'label' => 'Advanced Parameters',
                    'icon' => 'advanced',
                    'match' => ['admin.employees.*', 'admin.roles.*', 'admin.mail.*', 'admin.webservice.*'],
                    'children' => [
                        [
                            'label' => 'Team',
                            'route' => 'admin.employees.index',
                            'match' => ['admin.employees.*', 'admin.roles.*'],
                            'permission' => 'tab.employees',
                        ],
                        [
                            'label' => 'E-mail',
                            'route' => 'admin.mail.index',
                            'match' => 'admin.mail.*',
                            'permission' => 'tab.mail',
                        ],
                        [
                            'label' => 'Webservice',
                            'route' => 'admin.webservice.index',
                            'match' => 'admin.webservice.*',
                            'permission' => 'tab.advanced',
                        ],
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tab permissions catalog (used on Roles screen checkboxes)
    |--------------------------------------------------------------------------
    */
    'tab_permissions' => [
        'tab.dashboard' => 'Dashboard',
        'tab.orders' => 'Orders',
        'tab.customers' => 'Customers',
        'tab.employees' => 'Employees',
        'tab.reports' => 'Reports / Stats',
        'tab.catalog' => 'Products / Catalog',
        'tab.shipping' => 'Shipping / Carriers',
        'tab.payment' => 'Payment methods',
        'tab.settings' => 'Shop Parameters',
        'tab.mail' => 'E-mail',
        'tab.roles' => 'Roles & Permissions',
        'tab.advanced' => 'Advanced Parameters',
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings groups (Shop / Orders / Products / …)
    | Read anywhere with: configuration('PS_ORDER_PREFIX')
    |--------------------------------------------------------------------------
    */
    'settings_groups' => [
        'shop' => [
            'label' => 'Shop settings',
            'description' => 'Company identity, currency, language and contact details.',
            'fields' => [
                'PS_SHOP_NAME' => [
                    'label' => 'Shop / Company name',
                    'type' => 'text',
                    'section' => 'Company identity',
                    'default' => 'Laravel ERP',
                    'rules' => 'required|string|max:150',
                ],
                'PS_SHOP_LOGO' => [
                    'label' => 'Shop logo',
                    'type' => 'image',
                    'section' => 'Company identity',
                    'default' => '',
                    'rules' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
                    'hint' => 'Shown as a circle on login, signup, browser tab, and the top menu. JPG, PNG, WebP or GIF — max 2 MB.',
                ],
                'PS_SHOP_EMAIL' => [
                    'label' => 'Shop email',
                    'type' => 'email',
                    'section' => 'Company identity',
                    'default' => '',
                    'rules' => 'required|email|max:150',
                ],
                'PS_SHOP_PHONE' => [
                    'label' => 'Phone',
                    'type' => 'text',
                    'section' => 'Company identity',
                    'default' => '',
                    'rules' => 'nullable|string|max:50',
                ],
                'PS_SHOP_ADDRESS' => [
                    'label' => 'Address',
                    'type' => 'textarea',
                    'section' => 'Shop address',
                    'default' => '',
                    'rules' => 'nullable|string|max:500',
                ],
                'PS_SHOP_CITY' => [
                    'label' => 'City',
                    'type' => 'text',
                    'section' => 'Shop address',
                    'default' => '',
                    'rules' => 'nullable|string|max:100',
                ],
                'PS_SHOP_POSTCODE' => [
                    'label' => 'Zip / Postal code',
                    'type' => 'text',
                    'section' => 'Shop address',
                    'default' => '',
                    'rules' => 'nullable|string|max:30',
                ],
                'PS_SHOP_COUNTRY' => [
                    'label' => 'Country',
                    'type' => 'country',
                    'section' => 'Shop address',
                    'default' => '',
                    'rules' => 'nullable|string|max:100',
                ],
                'PS_SHOP_STATE' => [
                    'label' => 'State / Province',
                    'type' => 'state',
                    'section' => 'Shop address',
                    'default' => '',
                    'rules' => 'nullable|string|max:100',
                ],
                'PS_CURRENCY_DEFAULT' => [
                    'label' => 'Currency',
                    'type' => 'static',
                    'section' => 'Regional settings',
                    'default' => 'INR',
                    'value' => 'INR — Indian Rupee (₹)',
                    'hint' => 'This ERP uses Indian Rupees (INR) only.',
                ],
                'PS_TIMEZONE' => [
                    'label' => 'Timezone',
                    'type' => 'text',
                    'section' => 'Regional settings',
                    'default' => 'Asia/Kolkata',
                    'rules' => 'required|string|max:64',
                    'hint' => 'e.g. Asia/Kolkata, UTC',
                ],
                'PS_TAX_ENABLED' => [
                    'label' => 'Enable tax',
                    'type' => 'boolean',
                    'section' => 'Tax settings',
                    'default' => '0',
                    'rules' => 'nullable|boolean',
                ],
                'PS_TAX_RATE_DEFAULT' => [
                    'label' => 'Default tax rate (%)',
                    'type' => 'number',
                    'section' => 'Tax settings',
                    'default' => '0',
                    'rules' => 'nullable|numeric|min:0|max:100',
                ],
            ],
        ],
        'orders' => [
            'label' => 'Order settings',
            'description' => 'Order workflow, stock, invoicing and validation rules used by the ERP.',
            'fields' => [
                'PS_ORDER_PREFIX' => [
                    'label' => 'Order reference prefix',
                    'type' => 'text',
                    'default' => 'ORD',
                    'rules' => 'required|string|max:20',
                    'hint' => 'Used before the generated unique order reference.',
                ],
                'PS_ORDER_DEFAULT_STATUS' => [
                    'label' => 'Default order status',
                    'type' => 'select',
                    'default' => 'pending',
                    'options' => [
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'paid' => 'Paid',
                        'shipped' => 'Shipped',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ],
                    'rules' => 'required|in:pending,processing,paid,shipped,completed,cancelled',
                ],
                'PS_ORDER_MIN_AMOUNT' => [
                    'label' => 'Minimum order amount',
                    'type' => 'number',
                    'default' => '0',
                    'rules' => 'nullable|numeric|min:0',
                ],
                'PS_ORDER_DECREMENT_STOCK' => [
                    'label' => 'Decrease stock on order',
                    'type' => 'boolean',
                    'default' => '1',
                    'rules' => 'nullable|boolean',
                ],
                'PS_ORDER_AUTO_INVOICE' => [
                    'label' => 'Auto-create invoice on paid',
                    'type' => 'boolean',
                    'default' => '1',
                    'rules' => 'nullable|boolean',
                ],
                'PS_ORDER_NOTES_REQUIRED' => [
                    'label' => 'Order notes required',
                    'type' => 'boolean',
                    'default' => '0',
                    'rules' => 'nullable|boolean',
                ],
            ],
        ],
        'products' => [
            'label' => 'Product settings',
            'description' => 'Catalog, stock and pricing defaults used across the ERP.',
            'fields' => [
                'PS_PRODUCT_STOCK_MANAGEMENT' => [
                    'label' => 'Enable stock management',
                    'type' => 'boolean',
                    'default' => '1',
                    'rules' => 'nullable|boolean',
                ],
                'PS_PRODUCT_ALLOW_OOS' => [
                    'label' => 'Allow orders when out of stock',
                    'type' => 'boolean',
                    'default' => '0',
                    'rules' => 'nullable|boolean',
                ],
                'PS_PRODUCT_SKU_REQUIRED' => [
                    'label' => 'SKU required',
                    'type' => 'boolean',
                    'default' => '1',
                    'rules' => 'nullable|boolean',
                ],
                'PS_PRODUCT_DEFAULT_TYPE' => [
                    'label' => 'Default product type',
                    'type' => 'select',
                    'default' => 'product',
                    'options' => [
                        'product' => 'Standard product',
                        'service' => 'Service',
                        'pack' => 'Pack of products',
                        'virtual' => 'Virtual product',
                    ],
                    'rules' => 'required|in:product,service,pack,virtual',
                ],
                'PS_PACK_STOCK_TYPE' => [
                    'label' => 'Pack stock decrease',
                    'type' => 'select',
                    'default' => 'both',
                    'options' => [
                        'pack' => 'Decrease pack stock only',
                        'products' => 'Decrease products in pack only',
                        'both' => 'Decrease both pack and products in pack',
                    ],
                    'rules' => 'required|in:pack,products,both',
                    'hint' => 'Applied when a pack is ordered and stock decrement is enabled.',
                ],
                'PS_PRODUCT_LOW_STOCK' => [
                    'label' => 'Low stock threshold',
                    'type' => 'number',
                    'default' => '5',
                    'rules' => 'nullable|numeric|min:0',
                    'hint' => 'Alert when quantity falls to this level',
                ],
                'PS_PRODUCT_WEIGHT_UNIT' => [
                    'label' => 'Weight unit',
                    'type' => 'select',
                    'default' => 'kg',
                    'options' => [
                        'kg' => 'kg',
                        'g' => 'g',
                        'lb' => 'lb',
                    ],
                    'rules' => 'required|in:kg,g,lb',
                ],
                'PS_PRODUCT_DIMENSION_UNIT' => [
                    'label' => 'Dimension unit',
                    'type' => 'select',
                    'default' => 'cm',
                    'options' => [
                        'cm' => 'cm',
                        'mm' => 'mm',
                        'in' => 'in',
                    ],
                    'rules' => 'required|in:cm,mm,in',
                ],
            ],
        ],
        'customers' => [
            'label' => 'Customer settings',
            'description' => 'Customer record requirements and account defaults used by the ERP.',
            'fields' => [
                'PS_CUSTOMER_REQUIRE_PHONE' => [
                    'label' => 'Phone required',
                    'type' => 'boolean',
                    'default' => '0',
                    'rules' => 'nullable|boolean',
                ],
                'PS_CUSTOMER_CODE_PREFIX' => [
                    'label' => 'Customer code prefix',
                    'type' => 'text',
                    'default' => 'CUS',
                    'rules' => 'required|string|max:20',
                ],
                'PS_CUSTOMER_DEFAULT_GROUP' => [
                    'label' => 'Default customer type',
                    'type' => 'select',
                    'default' => 'individual',
                    'options' => [
                        'individual' => 'Individual',
                        'company' => 'Company',
                    ],
                    'rules' => 'required|in:individual,company',
                ],
            ],
        ],
    ],
];

