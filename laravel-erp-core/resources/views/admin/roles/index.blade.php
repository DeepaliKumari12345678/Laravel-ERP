@extends('admin.layouts.app')

@section('title', 'Roles')

@section('content')
    <div class="ps-breadcrumb">Team &gt; Roles</div>
    <div class="team-head">
        <div>
            <h1 class="page-title">Team</h1>
            <p class="page-sub" style="margin:0;">Define staff roles, then assign access from Permissions.</p>
        </div>
        @if($isSuperAdmin)
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">+ Add role</a>
        @endif
    </div>

    @include('admin.team._tabs')

    <div class="card" style="padding:0;overflow:hidden;">
        <div class="team-toolbar">
            <h3>Roles ({{ $roles->total() }})</h3>
            <span class="team-muted">System roles are protected from deletion.</span>
        </div>
        <form method="get" action="{{ route('admin.roles.index') }}" style="display:grid;grid-template-columns:100px 1fr auto auto;gap:.4rem;align-items:end;padding:.8rem 1rem;background:#fbfcfc;border-bottom:1px solid var(--ps-line);">
            <label>ID<input type="number" name="id" value="{{ request('id') }}"></label>
            <label>Name<input name="name" value="{{ request('name') }}" placeholder="Search role"></label>
            <button class="btn btn-primary" type="submit">Search</button>
            @if(collect(request()->except('page'))->filter(fn ($v) => filled($v))->isNotEmpty())
                <a class="btn btn-ghost" href="{{ route('admin.roles.index') }}">Reset</a>
            @endif
        </form>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Role</th>
                    <th>Description</th>
                    <th>Permissions</th>
                    <th>Employees</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td>{{ $role->id }}</td>
                        <td>
                            <strong>{{ $role->display_name }}</strong>
                            <div class="team-muted">{{ $role->name }}</div>
                            @if($role->is_system)
                                <span class="badge badge-core" style="margin-top:.25rem;">System</span>
                            @endif
                        </td>
                        <td>{{ $role->description ?: '—' }}</td>
                        <td>
                            @if($role->name === 'super_admin')
                                <span class="badge badge-on">All tabs</span>
                            @else
                                {{ $role->permissions->where('group', 'tab')->count() }} areas
                            @endif
                        </td>
                        <td>{{ $role->employees_count }}</td>
                        <td>
                            <div class="team-actions">
                                <a class="team-icon-btn" href="{{ route('admin.roles.permissions', ['role' => $role]) }}" title="Permissions">✓</a>
                                @if($isSuperAdmin)
                                    <a class="team-icon-btn" href="{{ route('admin.roles.edit', $role) }}" title="Edit">✎</a>
                                    @unless($role->is_system || $role->name === 'super_admin')
                                        <form method="post" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                                            @csrf @method('DELETE')
                                            <button class="team-icon-btn" type="submit" title="Delete" style="color:var(--danger);">×</button>
                                        </form>
                                    @endunless
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="color:var(--ps-muted);">No roles found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem;">{{ $roles->links() }}</div>
    </div>
@endsection
