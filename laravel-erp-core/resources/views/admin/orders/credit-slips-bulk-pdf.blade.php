<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Credit slips' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f3f3f3; }
    </style>
</head>
<body>
    <h1>{{ $title ?? 'Credit slips' }}</h1>
    @isset($from)
        <p>From {{ $from }} to {{ $to }}</p>
    @endisset

    <table>
        <thead>
            <tr>
                <th>Number</th>
                <th>Order</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Reason</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($slips as $slip)
                <tr>
                    <td>{{ $slip->number }}</td>
                    <td>{{ $slip->order?->reference }}</td>
                    <td>{{ $slip->customer?->full_name }}</td>
                    <td>{{ number_format((float) $slip->amount, 2) }} {{ $slip->currency }}</td>
                    <td>{{ $slip->reason ?: '—' }}</td>
                    <td>{{ optional($slip->issued_at)->format('Y-m-d') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
