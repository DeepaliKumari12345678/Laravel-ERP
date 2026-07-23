<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Delivery slips' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f3f3f3; }
    </style>
</head>
<body>
    <h1>{{ $title ?? 'Delivery slips' }}</h1>
    @isset($from)
        <p>From {{ $from }} to {{ $to }}</p>
    @endisset

    <table>
        <thead>
            <tr>
                <th>Number</th>
                <th>Order</th>
                <th>Customer</th>
                <th>Carrier</th>
                <th>Tracking</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($slips as $slip)
                <tr>
                    <td>{{ $slip->number }}</td>
                    <td>{{ $slip->order?->reference }}</td>
                    <td>{{ $slip->customer?->full_name }}</td>
                    <td>{{ $slip->carrier ?: '—' }}</td>
                    <td>{{ $slip->tracking_number ?: '—' }}</td>
                    <td>{{ ucfirst($slip->status) }}</td>
                    <td>{{ optional($slip->shipped_at ?? $slip->created_at)->format('Y-m-d') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
