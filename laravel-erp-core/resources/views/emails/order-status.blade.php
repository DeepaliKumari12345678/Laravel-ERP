@extends('emails.layout')

@section('content')
    <p style="margin:0 0 12px;font-size:16px;">Hello {{ $customerName }},</p>
    <p style="margin:0 0 12px;line-height:1.5;">
        The status of your order <strong>{{ $order->reference }}</strong> has been updated.
    </p>
    <table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;margin:16px 0;border:1px solid #dbe2e8;border-radius:4px;">
        <tr><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;color:#6c868e;">Order</td><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;font-weight:700;">#{{ $order->id }} {{ $order->reference }}</td></tr>
        <tr><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;color:#6c868e;">New status</td><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;">{{ $statusLabel }}</td></tr>
        <tr><td style="padding:10px 12px;color:#6c868e;">Total</td><td style="padding:10px 12px;">{{ number_format((float)$order->total, 2) }} {{ $order->currency }}</td></tr>
    </table>
    @if(!empty($comment))
        <p style="margin:0;color:#6c868e;font-size:13px;">Note: {{ $comment }}</p>
    @endif
@endsection
