@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Edit employee' : 'New employee')

@section('content')
<div class="ps-breadcrumb">Team &gt; Employees &gt; {{ $mode === 'edit' ? 'Edit' : 'New employee' }}</div>
<div class="team-form-wrap" style="margin-bottom:.9rem;text-align:center;">
    <h1 class="page-title" style="margin:0 0 .25rem;">{{ $mode === 'edit' ? 'Edit employee' : 'New employee' }}</h1>
    <p class="page-sub" style="margin:0;">Account details and ERP access for a team member.</p>
</div>

@include('admin.team._tabs')

<div class="card team-form">
    <div class="card-head"><h3 style="margin:0;">Employee account</h3></div>
    <form method="post" enctype="multipart/form-data" action="{{ $mode === 'edit' ? route('admin.employees.update', $employee) : route('admin.employees.store') }}">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <div class="team-form-row">
            <div class="team-form-label">First name *</div>
            <div><input name="first_name" value="{{ old('first_name', $employee->first_name) }}" required></div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">Last name</div>
            <div><input name="last_name" value="{{ old('last_name', $employee->last_name) }}"></div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">Avatar</div>
            <div>
                @if($employee->avatar_url)
                    <img id="employee-avatar-preview" class="team-avatar-preview" src="{{ $employee->avatar_url }}" alt="{{ $employee->full_name }}">
                @else
                    <div id="employee-avatar-placeholder" class="team-avatar-preview">{{ $employee->initials ?: 'EMP' }}</div>
                    <img id="employee-avatar-preview" class="team-avatar-preview" src="" alt="Avatar preview" hidden>
                @endif
                <input id="employee-avatar-input" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <div class="team-muted" style="margin-top:.3rem;">JPG, PNG or WebP; maximum 2 MB.</div>
                @if($employee->avatar)
                    <label style="display:flex;align-items:center;gap:.45rem;margin-top:.55rem;color:var(--ps-ink);">
                        <input type="checkbox" name="remove_avatar" value="1" style="width:auto;"> Remove current avatar
                    </label>
                @endif
            </div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">Email address *</div>
            <div><input type="email" name="email" value="{{ old('email', $employee->user?->email) }}" required autocomplete="username"></div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">Password {{ $mode === 'create' ? '*' : '' }}</div>
            <div>
                <input type="password" name="password" @if($mode === 'create') required @endif minlength="8" autocomplete="new-password">
                <div class="team-muted" style="margin-top:.3rem;">{{ $mode === 'edit' ? 'Leave blank to keep the current password.' : 'Use at least 8 characters.' }}</div>
            </div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">Phone</div>
            <div><input name="phone" value="{{ old('phone', $employee->phone) }}"></div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">Role *</div>
            <div>
                <select name="role_id" required>
                    <option value="">— Select role —</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected((string) old('role_id', $employee->role_id) === (string) $role->id)>{{ $role->display_name }}</option>
                    @endforeach
                </select>
                <div class="team-muted" style="margin-top:.3rem;">Permissions are configured from the Permissions tab.</div>
            </div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">Active</div>
            <div>
                <label style="display:flex;align-items:center;gap:.55rem;color:var(--ps-ink);padding-top:.5rem;">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" name="active" value="1" style="width:auto;" @checked((bool) old('active', $employee->active))>
                    Allow this employee to sign in to the admin panel
                </label>
            </div>
        </div>

        <div class="team-form-actions">
            <a href="{{ route('admin.employees.index') }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary" type="submit">Save employee</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.getElementById('employee-avatar-input')?.addEventListener('change', event => {
    const file = event.target.files?.[0];
    const preview = document.getElementById('employee-avatar-preview');
    if (!file || !preview) return;
    preview.src = URL.createObjectURL(file);
    preview.hidden = false;
    document.getElementById('employee-avatar-placeholder')?.setAttribute('hidden', 'hidden');
});
</script>
@endpush
@endsection
