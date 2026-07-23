<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Invoices' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f3f3f3; }
    </style>
</head>
<body>
    <h1>{{ $title ?? 'Invoices' }}</h1>
    @isset($from)
        <p>From {{ $from }} to {{ $to }}</p>
    @endisset

    <table>
        <thead>
            <tr>
                <th>Number</th>
                <th>Order</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->number }}</td>
                    <td>{{ $invoice->order?->reference }}</td>
                    <td>{{ $invoice->customer?->full_name }}</td>
                    <td>{{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}</td>
                    <td>{{ optional($invoice->issued_at)->format('Y-m-d') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
