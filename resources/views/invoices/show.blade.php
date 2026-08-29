@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number)

@push('styles')
    @include('invoices.partials.styles')
@endpush

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom no-print">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <div>
                <h2 class="fs-4 m-0 fw-bold">Invoice Preview</h2>
                <div class="text-muted small">Review, print, or download invoice {{ $invoice->invoice_number }}</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('calendar.index') }}" class="btn btn-light border btn-sm">
                    <i class="bx bx-calendar me-1" aria-hidden="true"></i> Return to Calendar
                </a>
                <a href="{{ route('invoices.index') }}" class="btn btn-white border btn-sm text-muted">Back to invoices</a>
                <button type="button" onclick="window.print()" class="btn btn-white border btn-sm">
                    <i class="bx bx-printer" aria-hidden="true"></i> Print invoice
                </button>
                @if($balance > 0 && !in_array($invoice->status, ['paid', 'void']))
                    <a href="{{ route('payment-records.index', ['invoice_id' => $invoice->id]) }}" class="btn btn-success btn-sm px-3">
                        <i class="bx bx-credit-card" aria-hidden="true"></i> Add payment
                    </a>
                @endif
                <a href="{{ route('invoices.download', $invoice->id) }}" class="btn btn-primary btn-sm px-4">
                    <i class="bx bx-download" aria-hidden="true"></i> Download invoice
                </a>
            </div>
        </div>
    </nav>

    {{-- Session messages (e.g. after payment redirect) --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="invoice-preview-shell">
        @include('invoices.partials.document')
    </div>
@endsection
