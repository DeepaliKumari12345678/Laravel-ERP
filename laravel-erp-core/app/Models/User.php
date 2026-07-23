<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'user_type', 'active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function hasPermission(string $permission): bool
    {
        $this->loadMissing('employee.role.permissions');

        $employee = $this->employee;

        if (! $employee || ! $employee->role) {
            return false;
        }

        if ($employee->role->name === 'super_admin') {
            return true;
        }

        return $employee->role->permissions->contains('name', $permission);
    }

    public function isSuperAdmin(): bool
    {
        return $this->employee?->role?->name === 'super_admin';
    }

    public function isEmployee(): bool
    {
        return $this->user_type === 'employee';
    }

    /**
     * Filtered admin menu for the current user.
     *
     * @return list<array{section:string, items:list<array<string, mixed>>}>
     */
    public function adminMenu(): array
    {
        $menu = [];

        foreach (config('erp.menu', []) as $section) {
            $items = [];

            foreach ($section['items'] as $item) {
                if (! empty($item['children'])) {
                    $children = [];

                    foreach ($item['children'] as $child) {
                        if ($this->hasPermission($child['permission'])) {
                            $child['label'] = \App\Support\MenuTranslator::label($child['label']);
                            $children[] = $child;
                        }
                    }

                    if ($children !== []) {
                        $item['children'] = $children;
                        $item['label'] = \App\Support\MenuTranslator::label($item['label']);
                        $items[] = $item;
                    }

                    continue;
                }

                if ($this->hasPermission($item['permission'])) {
                    $item['label'] = \App\Support\MenuTranslator::label($item['label']);
                    $items[] = $item;
                }
            }

            if ($items !== []) {
                $menu[] = [
                    'section' => \App\Support\MenuTranslator::label($section['section']),
                    'items' => $items,
                ];
            }
        }

        return $menu;
    }
}
