@extends('admin.layouts.app')
@section('title', 'Employees')
@section('content')
<div class="ps-breadcrumb">Team &gt; Employees</div>
<div class="team-head">
    <div>
        <h1 class="page-title">Team</h1>
        <p class="page-sub" style="margin:0;">Manage staff accounts, roles, and access to the ERP.</p>
    </div>
    <a href="{{ route('admin.employees.create') }}" class="btn btn-primary">+ Add employee</a>
</div>

@include('admin.team._tabs')

<div class="card" style="padding:0;overflow:hidden;">
    <div class="team-toolbar">
        <h3>Employees ({{ $employees->total() }})</h3>
        <span class="team-muted">Only Super Admins can assign the Super Admin role.</span>
    </div>

    <form method="get" action="{{ route('admin.employees.index') }}" class="team-filters">
        <label>ID<input type="number" name="id" value="{{ request('id') }}"></label>
        <label>Name<input name="name" value="{{ request('name') }}" placeholder="Search name"></label>
        <label>Email<input name="email" value="{{ request('email') }}" placeholder="Search email"></label>
        <label>Role
            <select name="role_id">
                <option value="">All roles</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" @selected((string) request('role_id') === (string) $role->id)>{{ $role->display_name }}</option>
                @endforeach
            </select>
        </label>
        <label>Active
            <select name="active">
                <option value="">All</option>
                <option value="1" @selected(request('active') === '1')>Yes</option>
                <option value="0" @selected(request('active') === '0')>No</option>
            </select>
        </label>
        <button class="btn btn-primary" type="submit">Search</button>
        @if(collect(request()->except('page'))->filter(fn ($v) => filled($v))->isNotEmpty())
            <a class="btn btn-ghost" href="{{ route('admin.employees.index') }}">Reset</a>
        @endif
    </form>

    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Employee</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Last login</th>
                    <th>Active</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($employees as $employee)
                <tr>
                    <td>{{ $employee->id }}</td>
                    <td>
                        <div class="team-person">
                            @if($employee->avatar_url)
                                <img class="team-avatar" src="{{ $employee->avatar_url }}" alt="{{ $employee->full_name }}">
                            @else
                                <span class="team-avatar">{{ $employee->initials ?: 'E' }}</span>
                            @endif
                            <span>
                                <strong>{{ $employee->full_name }}</strong>
                                <span class="team-muted" style="display:block;">{{ $employee->employee_code }}</span>
                            </span>
                        </div>
                    </td>
                    <td>{{ $employee->user?->email }}</td>
                    <td>{{ $employee->phone ?: '—' }}</td>
                    <td><span class="badge badge-off">{{ $employee->role?->display_name ?? 'No role' }}</span></td>
                    <td>{{ $employee->last_login_at?->format('d M Y, H:i') ?? 'Never' }}</td>
                    <td>
                        <form method="post" action="{{ route('admin.employees.toggle', $employee) }}">
                            @csrf @method('PUT')
                            <button class="team-status {{ $employee->active ? 'on' : 'off' }}" type="submit">
                                {{ $employee->active ? 'Enabled' : 'Disabled' }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="team-actions">
                            <a class="team-icon-btn" href="{{ route('admin.employees.edit', $employee) }}" title="Edit">✎</a>
                            @if($employee->user_id !== auth()->id())
                                <form method="post" action="{{ route('admin.employees.destroy', $employee) }}" onsubmit="return confirm('Delete this employee account?')">
                                    @csrf @method('DELETE')
                                    <button class="team-icon-btn" type="submit" title="Delete" style="color:var(--danger);">×</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" style="color:var(--ps-muted);">No employees found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:1rem;">{{ $employees->links() }}</div>
</div>
@endsection
