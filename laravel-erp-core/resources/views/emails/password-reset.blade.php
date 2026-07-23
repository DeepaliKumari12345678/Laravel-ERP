@extends('emails.layout')

@section('content')
    <p style="margin:0 0 12px;font-size:16px;">Hello {{ $name }},</p>
    <p style="margin:0 0 12px;line-height:1.5;">
        We received a request to reset your password for <strong>{{ $shopName }}</strong>.
    </p>
    <p style="margin:0 0 18px;">
        <a href="{{ $resetUrl }}" style="display:inline-block;background:#25b9d7;color:#fff;text-decoration:none;padding:10px 16px;border-radius:4px;font-weight:700;">Reset password</a>
    </p>
    <p style="margin:0 0 8px;color:#6c868e;font-size:13px;line-height:1.45;word-break:break-all;">
        Or copy this link:<br>{{ $resetUrl }}
    </p>
    <p style="margin:0;color:#6c868e;font-size:13px;">If you did not request this, you can ignore this email.</p>
@endsection
