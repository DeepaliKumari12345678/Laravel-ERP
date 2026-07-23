@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Edit role' : 'New role')

@section('content')
<div class="ps-breadcrumb">Team &gt; Roles &gt; {{ $mode === 'edit' ? 'Edit' : 'New role' }}</div>
<div class="team-form-wrap" style="margin-bottom:.9rem;text-align:center;">
    <h1 class="page-title" style="margin:0 0 .25rem;">{{ $mode === 'edit' ? 'Edit role' : 'New role' }}</h1>
    <p class="page-sub" style="margin:0;">Use a clear name that describes this team member’s responsibility.</p>
</div>

@include('admin.team._tabs')

<div class="card team-form">
    <div class="card-head"><h3 style="margin:0;">Role</h3></div>
    <form method="post" action="{{ $mode === 'edit' ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <div class="team-form-row">
            <div class="team-form-label">Name *</div>
            <div>
                <input name="display_name" value="{{ old('display_name', $role->display_name) }}" placeholder="e.g. Sales Manager" required>
                @if($mode === 'edit')
                    <div class="team-muted" style="margin-top:.3rem;">Internal key: {{ $role->name }}</div>
                @endif
            </div>
        </div>
        <div class="team-form-row">
            <div class="team-form-label">Description</div>
            <div>
                <textarea name="description" rows="3" placeholder="What is this role responsible for?">{{ old('description', $role->description) }}</textarea>
            </div>
        </div>
        @if($mode === 'edit' && $role->is_system)
            <div class="team-form-row">
                <div class="team-form-label">System role</div>
                <div class="team-muted" style="padding-top:.55rem;">This protected role can be renamed, but it cannot be deleted.</div>
            </div>
        @endif

        <div class="team-form-actions">
            <a href="{{ route('admin.roles.index') }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary" type="submit">{{ $mode === 'edit' ? 'Save role' : 'Create role' }}</button>
        </div>
    </form>
</div>
@endsection
