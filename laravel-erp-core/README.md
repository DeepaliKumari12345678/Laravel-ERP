# Laravel ERP Base

Simple reusable Laravel starter for every client project.

**Idea:** copy this project → keep the same `employees`, `customers`, `products`, `orders`, `roles` tables → only add new tables for that client (patients, tickets, projects…).

## Always shared (do not recreate)

| Table | Purpose |
|---|---|
| `users` | Login accounts |
| `employees` | Back-office staff |
| `customers` | Contacts / clients |
| `roles` / `permissions` | Access control |
| `products` / `categories` | Catalog |
| `orders` / `order_items` | Sales |
| `configurations` | Settings key/value |
| `media` / `audit_logs` | Files & activity |

## Quick start

```bash
cd laravel-erp-core
composer install
php artisan migrate:fresh --seed
php artisan serve --port=8010
```

- Admin: http://127.0.0.1:8010/admin  
- Login: `admin@erp.local` / `password`

## New client project (Hospital / CRM / custom)

1. Copy this folder (or clone the repo as a new project).
2. Keep base tables and admin as-is.
3. Add only domain migrations for that client.
4. Link domain records to existing `customers` / `employees` with foreign keys.

Example:

```php
// projects.customer_id → customers.id
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->constrained(); // reuse customers
    $table->string('code')->unique();
    // ...
});
```

## What this base includes

- Admin login + RBAC  
- Employees, Customers, Products, Orders, Roles, Settings, Reports  
- Simple sidebar back office  

No Module Manager. No install/uninstall modules. Just one solid base you reuse every time.
