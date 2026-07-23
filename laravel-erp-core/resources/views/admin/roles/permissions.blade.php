@extends('admin.layouts.app')

@section('title', 'Permissions')

@section('content')
<div class="ps-breadcrumb">Team &gt; Permissions</div>
<div class="team-head">
    <div>
        <h1 class="page-title">Team</h1>
        <p class="page-sub" style="margin:0;">Choose which ERP areas each role can access.</p>
    </div>
</div>

@include('admin.team._tabs')

<div class="role-layout">
    <aside class="card role-list">
        <div style="padding:.45rem .55rem .75rem;font-size:.76rem;font-weight:700;color:var(--ps-muted);text-transform:uppercase;">Roles</div>
        @foreach($roles as $role)
            <a href="{{ route('admin.roles.permissions', ['role' => $role]) }}" class="{{ $selectedRole?->id === $role->id ? 'active' : '' }}">
                <strong>{{ $role->display_name }}</strong>
                <span style="display:block;font-size:.72rem;opacity:.7;margin-top:.15rem;">{{ $role->employees_count }} employee{{ $role->employees_count === 1 ? '' : 's' }}</span>
            </a>
        @endforeach
    </aside>

    <div class="card">
        @if($selectedRole)
            <div class="card-head" style="align-items:flex-start;">
                <div>
                    <h3 style="margin:0 0 .25rem;">{{ $selectedRole->display_name }}</h3>
                    <p class="team-muted" style="margin:0;">{{ $selectedRole->description ?: 'Configure access for this role.' }}</p>
                </div>
                @if($selectedRole->name === 'super_admin')
                    <span class="badge badge-on">Full access</span>
                @endif
            </div>

            @if($selectedRole->name === 'super_admin')
                <div style="padding:1rem;border:1px solid #bfe4eb;background:#eefafd;color:#256674;border-radius:5px;margin-bottom:1rem;">
                    Super Admin permissions cannot be restricted. This role always has access to every ERP area.
                </div>
            @endif

            <form method="post" action="{{ route('admin.roles.permissions.update', $selectedRole) }}">
                @csrf @method('PUT')
                @php
                    $granted = $selectedRole->permissions->where('group', 'tab')->pluck('name');
                    $locked = !$isSuperAdmin || $selectedRole->name === 'super_admin';
                @endphp

                <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:.8rem;">
                    <strong style="font-size:.88rem;">ERP area access</strong>
                    @if(!$locked)
                        <label style="display:flex;align-items:center;gap:.45rem;color:var(--ps-ink);font-size:.78rem;">
                            <input id="permission-select-all" type="checkbox" style="width:auto;"> Select all
                        </label>
                    @endif
                </div>

                <div class="permission-grid">
                    @foreach($tabs as $permission => $label)
                        <label class="permission-item">
                            <input
                                class="permission-checkbox"
                                type="checkbox"
                                name="tabs[]"
                                value="{{ $permission }}"
                                style="width:auto;"
                                @checked($selectedRole->name === 'super_admin' || $granted->contains($permission))
                                @disabled($locked)
                            >
                            <span>
                                <strong style="display:block;font-size:.82rem;">{{ $label }}</strong>
                                <span class="team-muted">{{ $permission }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                @if(!$locked)
                    <div style="display:flex;justify-content:flex-end;margin-top:1rem;">
                        <button class="btn btn-primary" type="submit">Save permissions</button>
                    </div>
                @elseif(!$isSuperAdmin && $selectedRole->name !== 'super_admin')
                    <p class="team-muted" style="margin:1rem 0 0;">Only a Super Admin can change permissions.</p>
                @endif
            </form>
        @else
            <p style="color:var(--ps-muted);">No roles are available.</p>
        @endif
    </div>
</div>

@push('scripts')
<script>
const selectAll = document.getElementById('permission-select-all');
const permissionBoxes = [...document.querySelectorAll('.permission-checkbox:not(:disabled)')];
if (selectAll) {
    const syncSelectAll = () => {
        selectAll.checked = permissionBoxes.length > 0 && permissionBoxes.every(box => box.checked);
        selectAll.indeterminate = permissionBoxes.some(box => box.checked) && !selectAll.checked;
    };
    selectAll.addEventListener('change', () => permissionBoxes.forEach(box => { box.checked = selectAll.checked; }));
    permissionBoxes.forEach(box => box.addEventListener('change', syncSelectAll));
    syncSelectAll();
}
</script>
@endpush
@endsection
