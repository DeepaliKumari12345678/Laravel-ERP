@extends('emails.layout')

@section('content')
    <p style="margin:0 0 12px;font-size:16px;">Hello {{ $customer->full_name }},</p>
    <p style="margin:0 0 12px;line-height:1.5;">
        Your customer account has been created at <strong>{{ $shopName }}</strong>.
    </p>
    <table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;margin:16px 0;border:1px solid #dbe2e8;border-radius:4px;">
        <tr><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;color:#6c868e;">Customer code</td><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;font-weight:700;">{{ $customer->customer_code }}</td></tr>
        <tr><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;color:#6c868e;">Email</td><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;">{{ $customer->email }}</td></tr>
        <tr><td style="padding:10px 12px;color:#6c868e;">Type</td><td style="padding:10px 12px;">{{ ucfirst($customer->type) }}</td></tr>
    </table>
    <p style="margin:0;color:#6c868e;font-size:13px;line-height:1.45;">If you did not expect this email, you can ignore it.</p>
@endsection
