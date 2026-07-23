<?php

namespace App\Http\Controllers\Admin;

use App\Core\Mail\ErpMail;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CrudController extends Controller
{
    public function employees(Request $request): View
    {
        $query = Employee::query()->with(['user', 'role'])->latest();

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }
        if ($request->filled('name')) {
            $name = '%'.$request->string('name').'%';
            $query->where(fn ($q) => $q->where('first_name', 'like', $name)->orWhere('last_name', 'like', $name));
        }
        if ($request->filled('email')) {
            $query->whereHas('user', fn ($q) => $q->where('email', 'like', '%'.$request->string('email').'%'));
        }
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->integer('role_id'));
        }
        if ($request->filled('active')) {
            $query->where('active', $request->string('active') === '1');
        }

        $employees = $query->paginate(20)->withQueryString();
        $roles = Role::query()->orderBy('display_name')->get();
        $isSuperAdmin = auth()->user()?->employee?->role?->name === 'super_admin';

        return view('admin.employees.index', compact('employees', 'roles', 'isSuperAdmin'));
    }

    public function createEmployee(): View
    {
        return $this->employeeForm(new Employee(['active' => true]), 'create');
    }

    public function storeEmployee(Request $request): RedirectResponse
    {
        $data = $this->validatedEmployee($request);
        $role = Role::query()->findOrFail($data['role_id']);
        $this->authorizeRoleAssignment($role);
        $avatar = $request->file('avatar')?->store('employees/avatars', 'public');

        DB::transaction(function () use ($data, $role, $avatar) {
            $active = (bool) ($data['active'] ?? false);
            $user = User::query()->create([
                'name' => trim($data['first_name'].' '.($data['last_name'] ?? '')),
                'email' => $data['email'],
                'password' => $data['password'],
                'user_type' => 'employee',
                'active' => $active,
            ]);

            Employee::query()->create([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'employee_code' => 'EMP-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'avatar' => $avatar,
                'active' => $active,
            ]);
        });

        $roleLabel = $role->display_name ?: ($role->name === 'super_admin' ? 'Super Admin' : 'Employee');

        ErpMail::send($data['email'], 'Your '.$roleLabel.' account is ready', 'emails.employee-created', [
            'employeeName' => trim($data['first_name'].' '.($data['last_name'] ?? '')),
            'email' => $data['email'],
            'roleLabel' => $roleLabel,
            'loginUrl' => route('admin.login'),
        ]);

        $msg = $role->name === 'super_admin'
            ? 'Super Admin created. Welcome email sent (if mail is configured).'
            : 'Employee created. Welcome email sent (if mail is configured).';

        return redirect()->route('admin.employees.index')->with('success', $msg);
    }

    public function editEmployee(Employee $employee): View
    {
        $employee->load(['user', 'role']);

        return $this->employeeForm($employee, 'edit');
    }

    public function updateEmployee(Request $request, Employee $employee): RedirectResponse
    {
        $employee->load(['user', 'role']);
        $data = $this->validatedEmployee($request, $employee);
        $role = Role::query()->findOrFail($data['role_id']);
        $this->authorizeRoleAssignment($role, $employee);
        $this->guardLastSuperAdmin($employee, $role);

        if ($employee->user_id === auth()->id() && ! (bool) ($data['active'] ?? false)) {
            return back()->with('error', 'You cannot deactivate your own account.')->withInput();
        }

        $newAvatar = $request->file('avatar')?->store('employees/avatars', 'public');
        $oldAvatar = $employee->avatar;

        DB::transaction(function () use ($request, $employee, $data, $role, $newAvatar) {
            $active = (bool) ($data['active'] ?? false);
            $userData = [
                'name' => trim($data['first_name'].' '.($data['last_name'] ?? '')),
                'email' => $data['email'],
                'active' => $active,
            ];
            if (! empty($data['password'])) {
                $userData['password'] = $data['password'];
            }

            $employee->user->update($userData);
            $employee->update([
                'role_id' => $role->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'avatar' => $request->boolean('remove_avatar') ? null : ($newAvatar ?: $employee->avatar),
                'active' => $active,
            ]);
        });

        if (($newAvatar || $request->boolean('remove_avatar')) && $oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return redirect()->route('admin.employees.index')->with('success', 'Employee updated.');
    }

    public function toggleEmployee(Employee $employee): RedirectResponse
    {
        if ($employee->user_id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }
        if ($employee->role?->name === 'super_admin' && ! $this->currentUserIsSuperAdmin()) {
            abort(403, 'Only a Super Admin can change this account.');
        }

        $active = ! $employee->active;
        DB::transaction(function () use ($employee, $active) {
            $employee->update(['active' => $active]);
            $employee->user?->update(['active' => $active]);
        });

        return back()->with('success', $employee->full_name.' was '.($active ? 'enabled.' : 'disabled.'));
    }

    public function destroyEmployee(Employee $employee): RedirectResponse
    {
        if ($employee->user_id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($employee->role?->name === 'super_admin') {
            if (! $this->currentUserIsSuperAdmin()) {
                abort(403, 'Only a Super Admin can delete this account.');
            }
            $this->guardLastSuperAdmin($employee);
        }

        $avatar = $employee->avatar;
        $user = $employee->user;
        $user?->delete();

        if ($avatar) {
            Storage::disk('public')->delete($avatar);
        }

        return redirect()->route('admin.employees.index')->with('success', 'Employee deleted.');
    }

    public function updateEmployeeRole(Request $request, Employee $employee): RedirectResponse
    {
        if (! $this->currentUserIsSuperAdmin()) {
            return back()->with('error', 'Only a Super Admin can change roles.');
        }

        $data = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        // Prevent removing the last Super Admin
        $newRole = Role::query()->findOrFail($data['role_id']);
        $wasSuperAdmin = $employee->role?->name === 'super_admin';

        if ($wasSuperAdmin && $newRole->name !== 'super_admin') {
            $otherSuperAdmins = Employee::query()
                ->where('id', '!=', $employee->id)
                ->whereHas('role', fn ($q) => $q->where('name', 'super_admin'))
                ->exists();

            if (! $otherSuperAdmins) {
                return back()->with('error', 'Cannot demote the only Super Admin. Create another first.');
            }
        }

        $employee->update(['role_id' => $newRole->id]);

        return back()->with('success', $employee->full_name.' is now '.$newRole->display_name.'.');
    }

    protected function currentUserIsSuperAdmin(): bool
    {
        return auth()->user()?->employee?->role?->name === 'super_admin';
    }

    protected function employeeForm(Employee $employee, string $mode): View
    {
        if ($employee->exists && $employee->role?->name === 'super_admin' && ! $this->currentUserIsSuperAdmin()) {
            abort(403, 'Only a Super Admin can edit this account.');
        }

        $roles = Role::query()
            ->when(! $this->currentUserIsSuperAdmin(), fn ($query) => $query->where('name', '!=', 'super_admin'))
            ->orderBy('display_name')
            ->get();

        return view('admin.employees.form', [
            'employee' => $employee,
            'roles' => $roles,
            'mode' => $mode,
            'isSuperAdmin' => $this->currentUserIsSuperAdmin(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedEmployee(Request $request, ?Employee $employee = null): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($employee?->user_id)],
            'password' => [$employee ? 'nullable' : 'required', 'nullable', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ]);
    }

    protected function authorizeRoleAssignment(Role $role, ?Employee $employee = null): void
    {
        if (($role->name === 'super_admin' || $employee?->role?->name === 'super_admin')
            && ! $this->currentUserIsSuperAdmin()) {
            abort(403, 'Only a Super Admin can manage Super Admin accounts.');
        }
    }

    protected function guardLastSuperAdmin(Employee $employee, ?Role $newRole = null): void
    {
        if ($employee->role?->name !== 'super_admin' || $newRole?->name === 'super_admin') {
            return;
        }

        $hasAnother = Employee::query()
            ->where('id', '!=', $employee->id)
            ->whereHas('role', fn ($query) => $query->where('name', 'super_admin'))
            ->exists();

        if (! $hasAnother) {
            abort(422, 'The only Super Admin cannot be demoted or deleted.');
        }
    }
}
