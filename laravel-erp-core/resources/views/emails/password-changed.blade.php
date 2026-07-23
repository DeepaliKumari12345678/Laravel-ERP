@extends('emails.layout')

@section('content')
    <p style="margin:0 0 12px;font-size:16px;">Hello {{ $name }},</p>
    <p style="margin:0 0 12px;line-height:1.5;">
        Your password for <strong>{{ $shopName }}</strong> was changed successfully.
    </p>
    <p style="margin:0;color:#6c868e;font-size:13px;">If this wasn’t you, reset your password again and contact an administrator.</p>
@endsection
