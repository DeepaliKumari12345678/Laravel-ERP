<?php

namespace Database\Seeders;

use App\Core\Configuration\Configuration;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('erp.tab_permissions', []) as $name => $label) {
            Permission::query()->updateOrCreate(
                ['name' => $name],
                ['guard_name' => 'web', 'group' => 'tab']
            );
        }

        $superAdmin = Role::query()->updateOrCreate(
            ['name' => 'super_admin'],
            [
                'display_name' => 'Super Admin',
                'description' => 'Full access to all tabs',
                'is_system' => true,
            ]
        );

        $manager = Role::query()->updateOrCreate(
            ['name' => 'manager'],
            [
                'display_name' => 'Manager',
                'description' => 'Day-to-day operations',
                'is_system' => true,
            ]
        );

        $superAdmin->permissions()->sync(
            Permission::query()->where('group', 'tab')->pluck('id')
        );

        $manager->permissions()->sync(
            Permission::query()->whereIn('name', [
                'tab.dashboard',
                'tab.orders',
                'tab.customers',
                'tab.catalog',
                'tab.reports',
                'tab.shipping',
                'tab.payment',
            ])->pluck('id')
        );

        // Default Super Admin for local / QA testing (always reset on seed).
        $user = User::query()->updateOrCreate(
            ['email' => 'prestaworld12@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => 'gmail',
                'user_type' => 'employee',
                'active' => true,
            ]
        );

        $employee = Employee::query()->firstOrNew(['user_id' => $user->id]);
        $employee->role_id = $superAdmin->id;
        $employee->first_name = 'Super';
        $employee->last_name = 'Admin';
        $employee->active = true;
        if (! $employee->employee_code) {
            $employee->employee_code = Employee::query()->where('employee_code', 'EMP-SA-001')->exists()
                ? 'EMP-SA-'.str_pad((string) $user->id, 3, '0', STR_PAD_LEFT)
                : 'EMP-SA-001';
        }
        $employee->save();

        Configuration::updateValue('PS_SHOP_NAME', Configuration::get('PS_SHOP_NAME', 'Laravel ERP'));
        Configuration::updateValue('PS_SHOP_EMAIL', 'prestaworld12@gmail.com');
        Configuration::updateValue('PS_CURRENCY_DEFAULT', 'INR');
        Configuration::updateValue('PS_LANG_DEFAULT', 'en');
        Configuration::updateValue('PS_TIMEZONE', Configuration::get('PS_TIMEZONE', 'Asia/Kolkata'));
    }
}
