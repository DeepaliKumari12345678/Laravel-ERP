<?php

use App\Http\Controllers\Admin\AddressController;
use App\Http\Controllers\Admin\AttributeFeatureController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\CrudController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerOptionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImproveController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\MailController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderDocumentController;
use App\Http\Controllers\Admin\OrderStatusController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StatsController;
use App\Http\Controllers\Admin\WebserviceController;
use App\Models\CustomerAddress;
use App\Models\PaymentMethod;
use App\Models\ShippingCarrier;
use Illuminate\Support\Facades\Route;

Route::bind('address', fn (string $value) => CustomerAddress::query()->findOrFail($value));
Route::bind('carrier', fn (string $value) => ShippingCarrier::query()->findOrFail($value));
Route::bind('payment', fn (string $value) => PaymentMethod::query()->findOrFail($value));

Route::get('login', [AuthController::class, 'showLogin'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.store');
Route::get('signup', [AuthController::class, 'showSignup'])->name('signup');
Route::post('signup', [AuthController::class, 'signup'])->name('signup.store');

Route::get('forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/locations/states', [LocationController::class, 'states'])->name('locations.states');

    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('erp.permission:tab.dashboard')
        ->name('dashboard');

    Route::middleware('erp.permission:tab.employees')->group(function () {
        Route::get('/employees', [CrudController::class, 'employees'])->name('employees.index');
        Route::get('/employees/create', [CrudController::class, 'createEmployee'])->name('employees.create');
        Route::post('/employees', [CrudController::class, 'storeEmployee'])->name('employees.store');
        Route::get('/employees/{employee}/edit', [CrudController::class, 'editEmployee'])->name('employees.edit');
        Route::put('/employees/{employee}', [CrudController::class, 'updateEmployee'])->name('employees.update');
        Route::put('/employees/{employee}/toggle', [CrudController::class, 'toggleEmployee'])->name('employees.toggle');
        Route::put('/employees/{employee}/role', [CrudController::class, 'updateEmployeeRole'])->name('employees.role');
        Route::delete('/employees/{employee}', [CrudController::class, 'destroyEmployee'])->name('employees.destroy');
    });

    Route::middleware('erp.permission:tab.customers')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::put('/customers/{customer}/note', [CustomerController::class, 'updateNote'])->name('customers.note');
        Route::put('/customers/{customer}/toggle', [CustomerController::class, 'toggle'])->name('customers.toggle');

        Route::get('/customer-groups', [CustomerOptionController::class, 'groups'])->name('customer-groups.index');
        Route::get('/customer-groups/create', [CustomerOptionController::class, 'createGroup'])->name('customer-groups.create');
        Route::post('/customer-groups', [CustomerOptionController::class, 'storeGroup'])->name('customer-groups.store');
        Route::get('/customer-groups/{customerGroup}/edit', [CustomerOptionController::class, 'editGroup'])->name('customer-groups.edit');
        Route::put('/customer-groups/{customerGroup}', [CustomerOptionController::class, 'updateGroup'])->name('customer-groups.update');
        Route::delete('/customer-groups/{customerGroup}', [CustomerOptionController::class, 'destroyGroup'])->name('customer-groups.destroy');
        Route::get('/customer-titles', [CustomerOptionController::class, 'titles'])->name('customer-titles.index');
        Route::get('/customer-titles/create', [CustomerOptionController::class, 'createTitle'])->name('customer-titles.create');
        Route::post('/customer-titles', [CustomerOptionController::class, 'storeTitle'])->name('customer-titles.store');
        Route::post('/customer-titles/bulk', [CustomerOptionController::class, 'bulkTitles'])->name('customer-titles.bulk');
        Route::get('/customer-titles/{customerTitle}/edit', [CustomerOptionController::class, 'editTitle'])->name('customer-titles.edit');
        Route::put('/customer-titles/{customerTitle}', [CustomerOptionController::class, 'updateTitle'])->name('customer-titles.update');
        Route::delete('/customer-titles/{customerTitle}', [CustomerOptionController::class, 'destroyTitle'])->name('customer-titles.destroy');

        Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
        Route::get('/addresses/create', [AddressController::class, 'create'])->name('addresses.create');
        Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
        Route::get('/addresses/{address}/edit', [AddressController::class, 'edit'])->name('addresses.edit');
        Route::put('/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
        Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
    });

    Route::middleware('erp.permission:tab.catalog')->group(function () {
        Route::get('/catalog/products', [CatalogController::class, 'products'])->name('catalog.products');
        Route::post('/catalog/products/bulk', [CatalogController::class, 'bulkProducts'])->name('catalog.products.bulk');
        Route::get('/catalog/products/create', [CatalogController::class, 'createProduct'])->name('catalog.products.create');
        Route::post('/catalog/products', [CatalogController::class, 'storeProduct'])->name('catalog.products.store');
        Route::get('/catalog/products/{product}', [CatalogController::class, 'previewProduct'])->name('catalog.products.preview');
        Route::post('/catalog/products/{product}/duplicate', [CatalogController::class, 'duplicateProduct'])->name('catalog.products.duplicate');
        Route::get('/catalog/products/{product}/edit', [CatalogController::class, 'editProduct'])->name('catalog.products.edit');
        Route::put('/catalog/products/{product}', [CatalogController::class, 'updateProduct'])->name('catalog.products.update');
        Route::delete('/catalog/products/{product}', [CatalogController::class, 'destroyProduct'])->name('catalog.products.destroy');
        Route::put('/catalog/products/{product}/toggle', [CatalogController::class, 'toggleProduct'])->name('catalog.products.toggle');

        Route::get('/catalog/categories', [CatalogController::class, 'categories'])->name('catalog.categories');
        Route::get('/catalog/categories/create', [CatalogController::class, 'createCategory'])->name('catalog.categories.create');
        Route::post('/catalog/categories', [CatalogController::class, 'storeCategory'])->name('catalog.categories.store');
        Route::post('/catalog/categories/bulk', [CatalogController::class, 'bulkCategories'])->name('catalog.categories.bulk');
        Route::get('/catalog/categories/{category}/edit', [CatalogController::class, 'editCategory'])->name('catalog.categories.edit');
        Route::put('/catalog/categories/{category}', [CatalogController::class, 'updateCategory'])->name('catalog.categories.update');
        Route::put('/catalog/categories/{category}/toggle', [CatalogController::class, 'toggleCategory'])->name('catalog.categories.toggle');
        Route::delete('/catalog/categories/{category}', [CatalogController::class, 'destroyCategory'])->name('catalog.categories.destroy');

        Route::get('/catalog/attributes', [AttributeFeatureController::class, 'attributes'])->name('catalog.attributes');
        Route::get('/catalog/attributes/create', [AttributeFeatureController::class, 'createAttribute'])->name('catalog.attributes.create');
        Route::post('/catalog/attributes', [AttributeFeatureController::class, 'storeAttribute'])->name('catalog.attributes.store');
        Route::get('/catalog/attributes/values/create', [AttributeFeatureController::class, 'createAttributeValueGlobal'])->name('catalog.attribute-values.create');
        Route::post('/catalog/attributes/values', [AttributeFeatureController::class, 'storeAttributeValueGlobal'])->name('catalog.attribute-values.store');
        Route::get('/catalog/attributes/{attributeGroup}', [AttributeFeatureController::class, 'showAttribute'])->name('catalog.attributes.show');
        Route::get('/catalog/attributes/{attributeGroup}/edit', [AttributeFeatureController::class, 'editAttribute'])->name('catalog.attributes.edit');
        Route::put('/catalog/attributes/{attributeGroup}', [AttributeFeatureController::class, 'updateAttribute'])->name('catalog.attributes.update');
        Route::delete('/catalog/attributes/{attributeGroup}', [AttributeFeatureController::class, 'destroyAttribute'])->name('catalog.attributes.destroy');
        Route::get('/catalog/attributes/{attributeGroup}/values/create', [AttributeFeatureController::class, 'createAttributeValue'])->name('catalog.attributes.values.create');
        Route::post('/catalog/attributes/{attributeGroup}/values', [AttributeFeatureController::class, 'storeAttributeValue'])->name('catalog.attributes.values.store');
        Route::get('/catalog/attributes/{attributeGroup}/values/{attributeValue}/edit', [AttributeFeatureController::class, 'editAttributeValue'])->name('catalog.attributes.values.edit');
        Route::put('/catalog/attributes/{attributeGroup}/values/{attributeValue}', [AttributeFeatureController::class, 'updateAttributeValue'])->name('catalog.attributes.values.update');
        Route::delete('/catalog/attributes/{attributeGroup}/values/{attributeValue}', [AttributeFeatureController::class, 'destroyAttributeValue'])->name('catalog.attributes.values.destroy');

        Route::get('/catalog/features', [AttributeFeatureController::class, 'features'])->name('catalog.features');
        Route::get('/catalog/features/create', [AttributeFeatureController::class, 'createFeature'])->name('catalog.features.create');
        Route::post('/catalog/features', [AttributeFeatureController::class, 'storeFeature'])->name('catalog.features.store');
        Route::get('/catalog/features/values/create', [AttributeFeatureController::class, 'createFeatureValueGlobal'])->name('catalog.feature-values.create');
        Route::post('/catalog/features/values', [AttributeFeatureController::class, 'storeFeatureValueGlobal'])->name('catalog.feature-values.store');
        Route::get('/catalog/features/{feature}', [AttributeFeatureController::class, 'showFeature'])->name('catalog.features.show');
        Route::get('/catalog/features/{feature}/edit', [AttributeFeatureController::class, 'editFeature'])->name('catalog.features.edit');
        Route::put('/catalog/features/{feature}', [AttributeFeatureController::class, 'updateFeature'])->name('catalog.features.update');
        Route::delete('/catalog/features/{feature}', [AttributeFeatureController::class, 'destroyFeature'])->name('catalog.features.destroy');
        Route::get('/catalog/features/{feature}/values/create', [AttributeFeatureController::class, 'createFeatureValue'])->name('catalog.features.values.create');
        Route::post('/catalog/features/{feature}/values', [AttributeFeatureController::class, 'storeFeatureValue'])->name('catalog.features.values.store');
        Route::get('/catalog/features/{feature}/values/{featureValue}/edit', [AttributeFeatureController::class, 'editFeatureValue'])->name('catalog.features.values.edit');
        Route::put('/catalog/features/{feature}/values/{featureValue}', [AttributeFeatureController::class, 'updateFeatureValue'])->name('catalog.features.values.update');
        Route::delete('/catalog/features/{feature}/values/{featureValue}', [AttributeFeatureController::class, 'destroyFeatureValue'])->name('catalog.features.values.destroy');

        Route::get('/catalog/brands', [CatalogController::class, 'brands'])->name('catalog.brands');
        Route::get('/catalog/brands/create', [CatalogController::class, 'createBrand'])->name('catalog.brands.create');
        Route::post('/catalog/brands', [CatalogController::class, 'storeBrand'])->name('catalog.brands.store');
        Route::post('/catalog/brands/bulk', [CatalogController::class, 'bulkBrands'])->name('catalog.brands.bulk');
        Route::get('/catalog/brands/addresses/create', [CatalogController::class, 'createBrandAddress'])->name('catalog.brands.addresses.create');
        Route::post('/catalog/brands/addresses', [CatalogController::class, 'storeBrandAddress'])->name('catalog.brands.addresses.store');
        Route::get('/catalog/brands/addresses/{brandAddress}/edit', [CatalogController::class, 'editBrandAddress'])->name('catalog.brands.addresses.edit');
        Route::put('/catalog/brands/addresses/{brandAddress}', [CatalogController::class, 'updateBrandAddress'])->name('catalog.brands.addresses.update');
        Route::delete('/catalog/brands/addresses/{brandAddress}', [CatalogController::class, 'destroyBrandAddress'])->name('catalog.brands.addresses.destroy');
        Route::get('/catalog/brands/{brand}', [CatalogController::class, 'showBrand'])->name('catalog.brands.show');
        Route::get('/catalog/brands/{brand}/edit', [CatalogController::class, 'editBrand'])->name('catalog.brands.edit');
        Route::put('/catalog/brands/{brand}', [CatalogController::class, 'updateBrand'])->name('catalog.brands.update');
        Route::put('/catalog/brands/{brand}/toggle', [CatalogController::class, 'toggleBrand'])->name('catalog.brands.toggle');
        Route::delete('/catalog/brands/{brand}', [CatalogController::class, 'destroyBrand'])->name('catalog.brands.destroy');

        Route::get('/catalog/suppliers', [CatalogController::class, 'suppliers'])->name('catalog.suppliers');
        Route::get('/catalog/suppliers/create', [CatalogController::class, 'createSupplier'])->name('catalog.suppliers.create');
        Route::post('/catalog/suppliers', [CatalogController::class, 'storeSupplier'])->name('catalog.suppliers.store');
        Route::post('/catalog/suppliers/bulk', [CatalogController::class, 'bulkSuppliers'])->name('catalog.suppliers.bulk');
        Route::get('/catalog/suppliers/{supplier}', [CatalogController::class, 'showSupplier'])->name('catalog.suppliers.show');
        Route::get('/catalog/suppliers/{supplier}/edit', [CatalogController::class, 'editSupplier'])->name('catalog.suppliers.edit');
        Route::put('/catalog/suppliers/{supplier}', [CatalogController::class, 'updateSupplier'])->name('catalog.suppliers.update');
        Route::put('/catalog/suppliers/{supplier}/toggle', [CatalogController::class, 'toggleSupplier'])->name('catalog.suppliers.toggle');
        Route::delete('/catalog/suppliers/{supplier}', [CatalogController::class, 'destroySupplier'])->name('catalog.suppliers.destroy');

        Route::get('/catalog/stock', [CatalogController::class, 'stock'])->name('catalog.stock');
        Route::post('/catalog/stock/adjust', [CatalogController::class, 'adjustStock'])->name('catalog.stock.adjust');
        Route::post('/catalog/stock/bulk', [CatalogController::class, 'bulkAdjustStock'])->name('catalog.stock.bulk');
    });

    Route::middleware('erp.permission:tab.orders')->group(function () {
        Route::post('/orders/bulk', [OrderController::class, 'bulk'])->name('orders.bulk');
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
        Route::put('/orders/{order}/note', [OrderController::class, 'updateNote'])->name('orders.note');
        Route::post('/orders/{order}/payments', [OrderController::class, 'storePayment'])->name('orders.payments.store');
        Route::post('/orders/{order}/invoice', [OrderController::class, 'createInvoiceFromOrder'])->name('orders.invoice');

        Route::post('/orders/customer/select', [OrderController::class, 'selectCustomer'])->name('orders.customer.select');
        Route::post('/orders/customer', [OrderController::class, 'storeCustomer'])->name('orders.customer.store');
        Route::post('/orders/cart/add', [OrderController::class, 'addProduct'])->name('orders.cart.add');
        Route::post('/orders/cart/remove', [OrderController::class, 'removeProduct'])->name('orders.cart.remove');
        Route::post('/orders/cart/clear', [OrderController::class, 'clearCart'])->name('orders.cart.clear');

        Route::get('/invoices', [OrderController::class, 'invoices'])->name('invoices.index');
        Route::post('/invoices', [OrderController::class, 'storeInvoice'])->name('invoices.store');
        Route::get('/invoices/{invoice}', [OrderDocumentController::class, 'showInvoice'])->name('invoices.show');
        Route::get('/invoices/{invoice}/download', [OrderDocumentController::class, 'downloadInvoice'])->name('invoices.download');

        Route::post('/invoices/pdf-by-date', [OrderController::class, 'pdfByDate'])->name('invoices.pdf-by-date');
        Route::post('/invoices/pdf-by-status', [OrderController::class, 'pdfByStatus'])->name('invoices.pdf-by-status');
        Route::put('/invoices/options', [OrderController::class, 'updateInvoiceOptions'])->name('invoices.options.update');

        Route::get('/credit-slips', [OrderController::class, 'creditSlips'])->name('credit-slips.index');
        Route::post('/credit-slips', [OrderController::class, 'storeCreditSlip'])->name('credit-slips.store');
        Route::post('/credit-slips/pdf-by-date', [OrderController::class, 'pdfCreditSlipsByDate'])->name('credit-slips.pdf-by-date');
        Route::put('/credit-slips/options', [OrderController::class, 'updateCreditSlipOptions'])->name('credit-slips.options.update');
        Route::get('/credit-slips/{creditSlip}', [OrderDocumentController::class, 'showCreditSlip'])->name('credit-slips.show');
        Route::get('/credit-slips/{creditSlip}/download', [OrderDocumentController::class, 'downloadCreditSlip'])->name('credit-slips.download');
        Route::post('/orders/{order}/credit-slip', [OrderController::class, 'createCreditSlipFromOrder'])->name('orders.credit-slip');

        Route::get('/delivery-slips', [OrderController::class, 'deliverySlips'])->name('delivery-slips.index');
        Route::post('/delivery-slips', [OrderController::class, 'storeDeliverySlip'])->name('delivery-slips.store');
        Route::post('/delivery-slips/pdf-by-date', [OrderController::class, 'pdfDeliverySlipsByDate'])->name('delivery-slips.pdf-by-date');
        Route::put('/delivery-slips/options', [OrderController::class, 'updateDeliverySlipOptions'])->name('delivery-slips.options.update');
        Route::get('/delivery-slips/{deliverySlip}', [OrderDocumentController::class, 'showDeliverySlip'])->name('delivery-slips.show');
        Route::get('/delivery-slips/{deliverySlip}/download', [OrderDocumentController::class, 'downloadDeliverySlip'])->name('delivery-slips.download');
        Route::post('/orders/{order}/delivery-slip', [OrderController::class, 'createDeliverySlipFromOrder'])->name('orders.delivery-slip');
    });

    Route::middleware('erp.permission:tab.shipping')->group(function () {
        Route::get('/shipping/carriers', [ImproveController::class, 'carriers'])->name('shipping.carriers');
        Route::get('/shipping/carriers/create', [ImproveController::class, 'createCarrier'])->name('shipping.carriers.create');
        Route::post('/shipping/carriers', [ImproveController::class, 'storeCarrier'])->name('shipping.carriers.store');
        Route::get('/shipping/carriers/{carrier}/edit', [ImproveController::class, 'editCarrier'])->name('shipping.carriers.edit');
        Route::put('/shipping/carriers/{carrier}', [ImproveController::class, 'updateCarrier'])->name('shipping.carriers.update');
        Route::delete('/shipping/carriers/{carrier}', [ImproveController::class, 'destroyCarrier'])->name('shipping.carriers.destroy');
        Route::get('/shipping/preferences', [ImproveController::class, 'shippingPreferences'])->name('shipping.preferences');
        Route::put('/shipping/preferences', [ImproveController::class, 'updateShippingPreferences'])->name('shipping.preferences.update');
    });

    Route::middleware('erp.permission:tab.payment')->group(function () {
        Route::get('/payment/methods', [ImproveController::class, 'payments'])->name('payment.methods');
        Route::get('/payment/methods/create', [ImproveController::class, 'createPayment'])->name('payment.methods.create');
        Route::post('/payment/methods', [ImproveController::class, 'storePayment'])->name('payment.methods.store');
        Route::get('/payment/methods/{payment}/edit', [ImproveController::class, 'editPayment'])->name('payment.methods.edit');
        Route::put('/payment/methods/{payment}', [ImproveController::class, 'updatePayment'])->name('payment.methods.update');
        Route::put('/payment/methods/{payment}/toggle', [ImproveController::class, 'togglePayment'])->name('payment.methods.toggle');
        Route::delete('/payment/methods/{payment}', [ImproveController::class, 'destroyPayment'])->name('payment.methods.destroy');
    });

    Route::middleware('erp.permission:tab.advanced')->group(function () {
        Route::get('/webservice', [WebserviceController::class, 'index'])->name('webservice.index');
        Route::get('/webservice/create', [WebserviceController::class, 'create'])->name('webservice.create');
        Route::post('/webservice', [WebserviceController::class, 'store'])->name('webservice.store');
        Route::put('/webservice/configuration', [WebserviceController::class, 'updateConfiguration'])->name('webservice.configuration');
        Route::get('/webservice/{webserviceKey}/edit', [WebserviceController::class, 'edit'])->name('webservice.edit');
        Route::put('/webservice/{webserviceKey}', [WebserviceController::class, 'update'])->name('webservice.update');
        Route::put('/webservice/{webserviceKey}/toggle', [WebserviceController::class, 'toggle'])->name('webservice.toggle');
        Route::delete('/webservice/{webserviceKey}', [WebserviceController::class, 'destroy'])->name('webservice.destroy');
    });

    Route::middleware('erp.permission:tab.roles')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
        Route::put('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });

    Route::middleware('erp.permission:tab.settings')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::get('/order-workflow/statuses', [OrderStatusController::class, 'index'])->name('order-statuses.index');
        Route::get('/order-workflow/statuses/create', [OrderStatusController::class, 'create'])->name('order-statuses.create');
        Route::post('/order-workflow/statuses', [OrderStatusController::class, 'store'])->name('order-statuses.store');
        Route::get('/order-workflow/statuses/{orderStatus}/edit', [OrderStatusController::class, 'edit'])->name('order-statuses.edit');
        Route::put('/order-workflow/statuses/{orderStatus}', [OrderStatusController::class, 'update'])->name('order-statuses.update');
        Route::delete('/order-workflow/statuses/{orderStatus}', [OrderStatusController::class, 'destroy'])->name('order-statuses.destroy');
        Route::get('/settings/{group}', [SettingsController::class, 'index'])->name('settings.group');
        Route::put('/settings/{group}', [SettingsController::class, 'update'])->name('settings.update');
    });

    Route::middleware('erp.permission:tab.mail')->group(function () {
        Route::get('/mail', [MailController::class, 'index'])->name('mail.index');
        Route::post('/mail', [MailController::class, 'update'])->name('mail.update');
        Route::post('/mail/test', [MailController::class, 'test'])->name('mail.test');
    });

    Route::get('/reports', [StatsController::class, 'index'])
        ->middleware('erp.permission:tab.reports')
        ->name('reports.index');
});
