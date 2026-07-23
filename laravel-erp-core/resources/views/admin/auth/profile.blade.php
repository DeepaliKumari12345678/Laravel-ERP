@extends('admin.layouts.app')

@section('title', 'My Account')

@section('content')
    <h1 class="page-title">My Account</h1>
    <p class="page-sub">Change your name, email, or password anytime.</p>

    <div class="card" style="max-width:560px;">
        <form method="post" action="{{ route('admin.profile.update') }}">
            @csrf
            @method('PUT')
            <div class="form-row">
                <label>Full name
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                </label>
                <label>Email
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                </label>
            </div>
            <label>Current password
                <input type="password" name="current_password" required autocomplete="current-password">
            </label>
            <p style="color:var(--muted);font-size:0.82rem;margin:-0.35rem 0 0.85rem;">Required to confirm any change.</p>
            <div class="form-row">
                <label>New password (optional)
                    <input type="password" name="password" minlength="8" autocomplete="new-password">
                </label>
                <label>Confirm new password
                    <input type="password" name="password_confirmation" minlength="8" autocomplete="new-password">
                </label>
            </div>
            <button class="btn btn-primary" type="submit">Save credentials</button>
        </form>
    </div>
@endsection
