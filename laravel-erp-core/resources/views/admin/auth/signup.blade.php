<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Super Admin — {{ shop_name() }}</title>
    @if(shop_logo_url())
        <link rel="icon" href="{{ shop_logo_url() }}" type="image/png">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;600&family=IBM+Plex+Serif:wght@600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            font-family: "IBM Plex Sans", sans-serif;
            background:
                radial-gradient(circle at 20% 20%, rgba(20,150,127,0.18), transparent 35%),
                radial-gradient(circle at 80% 0%, rgba(196,92,38,0.16), transparent 30%),
                linear-gradient(160deg, #102a43, #243b53 55%, #0f6e56);
            color: #102a43;
        }
        .box {
            width: min(460px, 92vw); background: #fff; border-radius: 16px; padding: 2rem;
            box-shadow: 0 20px 50px rgba(16,42,67,0.25);
        }
        .auth-brand { display:flex; flex-direction:column; align-items:center; gap:0.75rem; margin-bottom:0.35rem; text-align:center; }
        .auth-logo {
            width:72px; height:72px; border-radius:50%; object-fit:cover;
            border:3px solid #e8f0f2; box-shadow:0 4px 14px rgba(16,42,67,0.12);
        }
        h1 { font-family: "IBM Plex Serif", serif; margin: 0 0 0.35rem; font-size: 1.65rem; text-align:center; }
        .auth-brand h1 { margin:0; }
        p { color: #667085; margin: 0 0 1.25rem; line-height: 1.45; text-align:center; }
        label { display: grid; gap: 0.35rem; margin-bottom: 0.85rem; font-size: 0.88rem; color: #667085; }
        input {
            border: 1px solid #e4e7ec; border-radius: 8px; padding: 0.65rem 0.75rem; font: inherit; color: #102a43;
        }
        input:focus { outline: 2px solid rgba(15,110,86,0.25); border-color: #0f6e56; }
        button {
            width: 100%; border: 0; border-radius: 8px; padding: 0.75rem; background: #0f6e56; color: #fff;
            font-weight: 600; cursor: pointer; margin-top: 0.35rem;
        }
        button:hover { background: #14967f; }
        .err { background: #fee4e2; color: #b42318; padding: 0.65rem 0.8rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; }
        .foot { margin-top: 1.1rem; text-align: center; font-size: 0.9rem; color: #667085; }
        .foot a { color: #0f6e56; font-weight: 600; text-decoration: none; }
        .hint { font-size: 0.78rem; color: #98a2b3; margin-top: -0.45rem; margin-bottom: 0.85rem; }
    </style>
</head>
<body>
<div class="box">
    @include('admin.partials.auth-brand')
    <p>Set up your company and admin account. You’ll log in next with this email and password.</p>

    @if($errors->any())
        <div class="err">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="post" action="{{ route('admin.signup.store') }}">
        @csrf
        <label>Company name
            <input type="text" name="company_name" value="{{ old('company_name') }}" placeholder="Acme Company" required autofocus>
        </label>
        <label>Your full name
            <input type="text" name="name" value="{{ old('name') }}" placeholder="John Doe" required>
        </label>
        <label>Email
            <input type="email" name="email" value="{{ old('email') }}" placeholder="admin@company.com" required>
        </label>
        <label>Password
            <input type="password" name="password" required minlength="8" autocomplete="new-password">
        </label>
        <p class="hint">Minimum 8 characters</p>
        <label>Confirm password
            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
        </label>
        <button type="submit">Create account</button>
    </form>

    <div class="foot">
        Already have an account? <a href="{{ route('admin.login') }}">Log in</a>
    </div>
</div>
</body>
</html>
