<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ configuration('PS_SHOP_NAME', config('erp.name')) }}</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;600&family=IBM+Plex+Serif:wght@600&display=swap" rel="stylesheet">
    <style>
        body { margin:0; font-family:"IBM Plex Sans",sans-serif; background:linear-gradient(160deg,#eef6f3,#f7f3ee); color:#102a43; }
        .hero { min-height:100vh; display:grid; place-items:center; padding:2rem; text-align:center; }
        h1 { font-family:"IBM Plex Serif",serif; font-size:clamp(2rem,5vw,3.4rem); margin:0 0 0.6rem; }
        p { color:#486581; max-width:36rem; margin:0 auto 1.5rem; }
        a { display:inline-block; background:#0f6e56; color:#fff; padding:0.75rem 1.2rem; border-radius:8px; text-decoration:none; font-weight:600; }
    </style>
</head>
<body>
<div class="hero">
    <div>
        <h1>{{ configuration('PS_SHOP_NAME', config('erp.name')) }}</h1>
        <p>Reusable Laravel ERP base. Create your Super Admin, then manage Employees, Customers, Orders and more.</p>
        <a href="{{ url('/admin/signup') }}">Get started</a>
    </div>
</div>
</body>
</html>
