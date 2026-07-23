<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeRolesTab();

        $query = Role::query()
            ->with('permissions')
            ->withCount('employees')
            ->orderByDesc('is_system')
            ->orderBy('display_name');

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }
        if ($request->filled('name')) {
            $query->where('display_name', 'like', '%'.$request->string('name').'%');
        }

        return view('admin.roles.index', [
            'roles' => $query->paginate(20)->withQueryString(),
            'isSuperAdmin' => $this->isSuperAdmin(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeRolesManage();

        return view('admin.roles.form', [
            'role' => new Role,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeRolesManage();

        $data = $this->validatedRole($request);

        $name = Str::slug($data['display_name'], '_');
        if ($name === '') {
            $name = 'role_'.Str::lower(Str::random(6));
        }

        if (Role::query()->where('name', $name)->exists()) {
            $name .= '_'.Str::lower(Str::random(4));
        }

        $this->ensureTabPermissionsExist();

        $role = Role::query()->create([
            'name' => $name,
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'is_system' => false,
        ]);

        $role->permissions()->sync($this->permissionIdsFromTabs(['tab.dashboard']));

        return redirect()
            ->route('admin.roles.permissions', ['role' => $role])
            ->with('success', "Role [{$role->display_name}] created. Choose its permissions.");
    }

    public function edit(Role $role): View
    {
        $this->authorizeRolesManage();

        return view('admin.roles.form', [
            'role' => $role,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeRolesManage();

        $data = $this->validatedRole($request);

        // Keep internal name for system roles; allow rename display only
        $role->update([
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->route('admin.roles.index')->with('success', "Role [{$role->display_name}] updated.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorizeRolesManage();

        if ($role->is_system || $role->name === 'super_admin') {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        if ($role->employees()->exists()) {
            return back()->with('error', 'Role is assigned to employees. Reassign them first.');
        }

        $role->permissions()->detach();
        $role->delete();

        return back()->with('success', 'Role deleted.');
    }

    public function permissions(Request $request): View
    {
        $this->authorizeRolesTab();
        $this->ensureTabPermissionsExist();

        $roles = Role::query()
            ->with('permissions')
            ->withCount('employees')
            ->orderByDesc('is_system')
            ->orderBy('display_name')
            ->get();

        $selectedRole = $roles->firstWhere('id', $request->integer('role')) ?? $roles->first();

        return view('admin.roles.permissions', [
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'tabs' => config('erp.tab_permissions', []),
            'isSuperAdmin' => $this->isSuperAdmin(),
        ]);
    }

    public function updatePermissions(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeRolesManage();
        $this->ensureTabPermissionsExist();

        $data = $request->validate([
            'tabs' => ['nullable', 'array'],
            'tabs.*' => ['string'],
        ]);

        if ($role->name === 'super_admin') {
            $role->permissions()->sync(Permission::query()->where('group', 'tab')->pluck('id'));
        } else {
            $role->permissions()->sync($this->permissionIdsFromTabs($data['tabs'] ?? []));
        }

        return redirect()
            ->route('admin.roles.permissions', ['role' => $role])
            ->with('success', "Permissions for [{$role->display_name}] updated.");
    }

    /**
     * @param  list<string>  $tabs
     * @return list<int>
     */
    protected function permissionIdsFromTabs(array $tabs): array
    {
        $allowed = array_keys(config('erp.tab_permissions', []));
        $tabs = array_values(array_intersect($tabs, $allowed));

        if ($tabs === []) {
            return [];
        }

        return Permission::query()
            ->whereIn('name', $tabs)
            ->pluck('id')
            ->all();
    }

    protected function ensureTabPermissionsExist(): void
    {
        foreach (config('erp.tab_permissions', []) as $name => $label) {
            Permission::query()->updateOrCreate(
                ['name' => $name],
                ['guard_name' => 'web', 'group' => 'tab']
            );
        }
    }

    protected function authorizeRolesTab(): void
    {
        if (! auth()->user()?->hasPermission('tab.roles')) {
            abort(403, 'You do not have access to Roles.');
        }
    }

    protected function authorizeRolesManage(): void
    {
        $this->authorizeRolesTab();

        if (! $this->isSuperAdmin()) {
            abort(403, 'Only Super Admin can manage roles.');
        }
    }

    protected function isSuperAdmin(): bool
    {
        return auth()->user()?->employee?->role?->name === 'super_admin';
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedRole(Request $request): array
    {
        return $request->validate([
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
