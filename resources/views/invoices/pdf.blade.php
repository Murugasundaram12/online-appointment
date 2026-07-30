<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    @include('invoices.partials.styles')
</head>
<body>
    @include('invoices.partials.document')
</body>
</html>
