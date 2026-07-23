<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password — {{ shop_name() }}</title>
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
            background: linear-gradient(160deg, #102a43, #243b53 55%, #0f6e56); color: #102a43;
        }
        .box { width: min(420px, 92vw); background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 20px 50px rgba(16,42,67,0.25); }
        .auth-brand { display:flex; flex-direction:column; align-items:center; gap:0.75rem; margin-bottom:0.35rem; text-align:center; }
        .auth-logo {
            width:72px; height:72px; border-radius:50%; object-fit:cover;
            border:3px solid #e8f0f2; box-shadow:0 4px 14px rgba(16,42,67,0.12);
        }
        h1 { font-family: "IBM Plex Serif", serif; margin: 0 0 0.35rem; font-size: 1.55rem; text-align:center; }
        .auth-brand h1 { margin:0; }
        p { color: #667085; margin: 0 0 1.2rem; line-height: 1.45; text-align:center; }
        label { display: grid; gap: 0.35rem; margin-bottom: 0.9rem; font-size: 0.88rem; color: #667085; }
        input { border: 1px solid #e4e7ec; border-radius: 8px; padding: 0.65rem 0.75rem; font: inherit; }
        button { width: 100%; border: 0; border-radius: 8px; padding: 0.75rem; background: #0f6e56; color: #fff; font-weight: 600; cursor: pointer; }
        .err { background: #fee4e2; color: #b42318; padding: 0.65rem 0.8rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; }
        .ok { background: #dcfae6; color: #027a48; padding: 0.65rem 0.8rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; word-break: break-all; }
        .foot { margin-top: 1.1rem; text-align: center; font-size: 0.9rem; color: #667085; }
        .foot a { color: #0f6e56; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
<div class="box">
    @include('admin.partials.auth-brand')
    <p>Enter your account email. We’ll send a reset link so you can set a new password.</p>

    @if(session('success'))
        <div class="ok">
            {{ session('success') }}
            @if(session('reset_url'))
                <div style="margin-top:0.6rem;"><a href="{{ session('reset_url') }}">Open reset link</a></div>
            @endif
        </div>
    @endif
    @if($errors->any())
        <div class="err">{{ $errors->first() }}</div>
    @endif

    <form method="post" action="{{ route('admin.password.email') }}">
        @csrf
        <label>Email
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </label>
        <button type="submit">Send reset link</button>
    </form>

    <div class="foot">
        <a href="{{ route('admin.login') }}">Back to login</a>
    </div>
</div>
</body>
</html>
