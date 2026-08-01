<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $payroll->payroll_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; margin: 0; }
        .page { padding: 34px; }
        .header { width: 100%; border-bottom: 1px solid #e2e8f0; padding-bottom: 18px; margin-bottom: 22px; }
        .left { float: left; width: 58%; }
        .right { float: right; width: 38%; text-align: right; }
        .clearfix { clear: both; }
        h1, h2, h3 { margin: 0; }
        .muted { color: #64748b; }
        .badge { display: inline-block; padding: 6px 12px; border-radius: 20px; background: #dcfce7; color: #15803d; font-weight: bold; text-transform: uppercase; }
        .badge.pending { background: #fef3c7; color: #b45309; }
        .badge.cancelled { background: #fee2e2; color: #b91c1c; }
        .section { margin-top: 22px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; color: #64748b; text-transform: uppercase; font-size: 10px; text-align: left; }
        th, td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
        .text-right { text-align: right; }
        .total { font-size: 16px; font-weight: bold; color: #1d4ed8; }
        .signature { margin-top: 70px; width: 44%; border-top: 1px solid #94a3b8; padding-top: 8px; color: #64748b; }
    </style>
</head>
<body>
<div class="page">
    @if(!empty($pdfFallback))
        <p class="muted">PDF generation failed. Printable HTML fallback shown.</p>
    @endif
    <div class="header">
        <div class="left">
            <h2>{{ $settings['business_name'] ?? config('app.name') }}</h2>
            <div class="muted">{{ $settings['business_address'] ?? '' }}</div>
            <div class="muted">{{ $settings['business_phone'] ?? '' }} {{ !empty($settings['business_email']) ? '| ' . $settings['business_email'] : '' }}</div>
        </div>
        <div class="right">
            <h1>PAYROLL</h1>
            <div>{{ $payroll->payroll_number }}</div>
            <div style="margin-top: 8px;"><span class="badge {{ $payroll->status }}">{{ ucfirst($payroll->display_status) }}</span></div>
            <div class="muted" style="margin-top: 8px;">Generated: {{ now()->format('M d, Y h:i A') }}</div>
        </div>
        <div class="clearfix"></div>
    </div>

    <table>
        <tr>
            <td><strong>Employee</strong><br>{{ optional($payroll->staff)->name ?? 'Not available' }}<br><span class="muted">{{ optional($payroll->staff)->email }}</span></td>
            <td><strong>Payroll Period</strong><br>{{ $payroll->period_start->format('M d, Y') }} - {{ $payroll->period_end->format('M d, Y') }}<br><span class="muted">Payment: {{ $payroll->payment_date ? $payroll->payment_date->format('M d, Y') : 'Not paid yet' }}</span></td>
        </tr>
    </table>

    @php
        $currency = $settings['currency'] ?? '$';
    @endphp
    <div class="section">
        <table>
            <thead><tr><th>Salary Breakdown</th><th class="text-right">Amount</th></tr></thead>
            <tbody>
            <tr><td>Basic Salary</td><td class="text-right">{{ $currency }}{{ number_format($payroll->salary_amount, 2) }}</td></tr>
            <tr><td>Commission</td><td class="text-right">{{ $currency }}{{ number_format($payroll->commission_amount ?? 0, 2) }}</td></tr>
            <tr><td>Bonus</td><td class="text-right">{{ $currency }}{{ number_format($payroll->bonus ?? 0, 2) }}</td></tr>
            <tr><td>Deductions</td><td class="text-right">-{{ $currency }}{{ number_format($payroll->deductions ?? 0, 2) }}</td></tr>
            <tr><td>Worked Hours</td><td class="text-right">{{ number_format($payroll->total_hours ?? 0, 2) }}</td></tr>
            <tr><td><strong>Total Payout</strong></td><td class="text-right total">{{ $currency }}{{ number_format($payroll->total_payout, 2) }}</td></tr>
            </tbody>
        </table>
    </div>

    @if($payroll->notes)
        <div class="section"><strong>Notes</strong><br>{{ $payroll->notes }}</div>
    @endif

    <div class="signature">Authorized signature</div>
</div>
</body>
</html>
