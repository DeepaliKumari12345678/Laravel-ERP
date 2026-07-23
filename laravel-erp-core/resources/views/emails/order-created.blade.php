@extends('emails.layout')

@section('content')
    <p style="margin:0 0 12px;font-size:16px;">Hello {{ $customerName }},</p>
    <p style="margin:0 0 12px;line-height:1.5;">
        Thank you for your order. Here is a summary of <strong>{{ $order->reference }}</strong>.
    </p>
    <table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;margin:16px 0;border:1px solid #dbe2e8;border-radius:4px;">
        <tr><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;color:#6c868e;">Order ID</td><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;font-weight:700;">#{{ $order->id }}</td></tr>
        <tr><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;color:#6c868e;">Reference</td><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;">{{ $order->reference }}</td></tr>
        <tr><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;color:#6c868e;">Status</td><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;">{{ ucfirst($order->status) }}</td></tr>
        <tr><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;color:#6c868e;">Payment</td><td style="padding:10px 12px;border-bottom:1px solid #f0f2f4;">{{ $order->payment_method ?: '—' }}</td></tr>
        <tr><td style="padding:10px 12px;color:#6c868e;">Total</td><td style="padding:10px 12px;font-weight:700;">{{ number_format((float)$order->total, 2) }} {{ $order->currency }}</td></tr>
    </table>

    @if($order->items->isNotEmpty())
        <p style="margin:0 0 8px;font-weight:700;">Products</p>
        <table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;border:1px solid #dbe2e8;border-radius:4px;margin-bottom:16px;">
            <tr style="background:#f7f9fa;">
                <td style="padding:8px 10px;font-size:12px;color:#6c868e;">Item</td>
                <td style="padding:8px 10px;font-size:12px;color:#6c868e;">Qty</td>
                <td style="padding:8px 10px;font-size:12px;color:#6c868e;text-align:right;">Total</td>
            </tr>
            @foreach($order->items as $item)
                <tr>
                    <td style="padding:8px 10px;border-top:1px solid #f0f2f4;">{{ $item->name }}</td>
                    <td style="padding:8px 10px;border-top:1px solid #f0f2f4;">{{ $item->quantity }}</td>
                    <td style="padding:8px 10px;border-top:1px solid #f0f2f4;text-align:right;">{{ number_format((float)$item->total, 2) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <p style="margin:0;color:#6c868e;font-size:13px;">We will keep you updated if the order status changes.</p>
@endsection
