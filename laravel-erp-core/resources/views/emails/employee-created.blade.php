@extends('emails.layout')

@section('content')
    <p style="margin:0 0 12px;font-size:16px;">Hello {{ $employeeName }},</p>
    <p style="margin:0 0 12px;line-height:1.5;">
        Your {{ $roleLabel }} account has been created for <strong>{{ $shopName }}</strong>.
    </p>
    <table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;margin:16px 0;border:1px solid #dbe2e8;border-radius:4px;">
        <tr><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;color:#6c868e;">Login email</td><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;font-weight:700;">{{ $email }}</td></tr>
        <tr><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;color:#6c868e;">Role</td><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;">{{ $roleLabel }}</td></tr>
        <tr><td style="padding:10px 12px;color:#6c868e;">Admin URL</td><td style="padding:10px 12px;"><a href="{{ $loginUrl }}" style="color:#1e94ab;">{{ $loginUrl }}</a></td></tr>
    </table>
    <p style="margin:0 0 12px;line-height:1.5;">
        Use the password shared by your administrator to sign in. You can change it later from My Account.
    </p>
    <p style="margin:0;">
        <a href="{{ $loginUrl }}" style="display:inline-block;background:#25b9d7;color:#fff;text-decoration:none;padding:10px 16px;border-radius:4px;font-weight:700;">Open admin login</a>
    </p>
@endsection
