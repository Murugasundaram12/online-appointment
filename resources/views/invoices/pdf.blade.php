<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #222; margin: 32px; }
        .row { display: flex; justify-content: space-between; gap: 24px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        th:last-child, td:last-child { text-align: right; }
        .total td { font-weight: bold; font-size: 18px; }
        .badge { padding: 6px 10px; border-radius: 4px; background: #eee; text-transform: uppercase; font-size: 12px; }
        @media print { body { margin: 16px; } }
    </style>
</head>
<body>
    <div class="row">
        <div>
            <h1>{{ \App\Models\BusinessSetting::where('key', 'business_name')->value('value') ?: config('app.name') }}</h1>
            <div class="muted">{{ \App\Models\BusinessSetting::where('key', 'business_address')->value('value') }}</div>
            <div class="muted">{{ \App\Models\BusinessSetting::where('key', 'business_email')->value('value') }}</div>
            <div class="muted">{{ \App\Models\BusinessSetting::where('key', 'business_phone')->value('value') }}</div>
        </div>
        <div style="text-align:right">
            <h2>Invoice</h2>
            <div>#{{ $invoice->invoice_number }}</div>
            <div>Issued: {{ optional($invoice->issued_date)->format('M j, Y') }}</div>
            <div>Due: {{ optional($invoice->due_date)->format('M j, Y') ?: '-' }}</div>
            <p><span class="badge">{{ str_replace('_', ' ', $invoice->status) }}</span></p>
        </div>
    </div>

    <div class="row" style="margin-top:32px">
        <div>
            <h3>Client</h3>
            <div>{{ optional($invoice->client)->name ?: 'N/A' }}</div>
            <div class="muted">{{ optional($invoice->client)->email }}</div>
            <div class="muted">{{ optional($invoice->client)->phone }}</div>
        </div>
        <div>
            <h3>Staff</h3>
            <div>{{ optional($invoice->staff)->name ?: 'N/A' }}</div>
            <div class="muted">{{ optional($invoice->staff)->email }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr><th>Description</th><th>Amount</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    {{ optional(optional($invoice->appointment)->service)->name ?: 'Service' }}
                    <div class="muted">
                        {{ optional(optional($invoice->appointment)->start_time)->format('M j, Y g:i A') }}
                    </div>
                </td>
                <td>${{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr><td>Paid</td><td>${{ number_format($invoice->paid_amount, 2) }}</td></tr>
            <tr class="total"><td>Balance</td><td>${{ number_format(max($invoice->total_amount - $invoice->paid_amount, 0), 2) }}</td></tr>
        </tfoot>
    </table>

    <h3>Payment History</h3>
    <table>
        <thead><tr><th>Date</th><th>Method</th><th>Transaction</th><th>Amount</th></tr></thead>
        <tbody>
            @forelse($invoice->payments as $payment)
                <tr>
                    <td>{{ optional($payment->payment_date)->format('M j, Y') }}</td>
                    <td>{{ ucfirst($payment->payment_method) }}</td>
                    <td>{{ $payment->transaction_id ?: '-' }}</td>
                    <td>${{ number_format($payment->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center">No payments recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
    <script>window.print();</script>
</body>
</html>
