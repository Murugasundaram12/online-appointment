@extends('layouts.app')

@section('title', 'Quotation ' . $quotation->quotation_number)

@push('styles')
    @include('quotations.partials.styles')
@endpush

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom no-print">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <div>
                <h2 class="fs-4 m-0 fw-bold">Quotation Preview</h2>
                <div class="text-muted small">Review quotation {{ $quotation->quotation_number }}</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('calendar.index') }}" class="btn btn-light border btn-sm">
                    <i class="bx bx-calendar me-1" aria-hidden="true"></i> Return to Calendar
                </a>
                <a href="{{ route('quotations.create') }}" class="btn btn-white border btn-sm text-muted">
                    <i class="bx bx-plus me-1" aria-hidden="true"></i> Create New Quotation
                </a>
                <button type="button" onclick="window.print()" class="btn btn-white border btn-sm">
                    <i class="bx bx-printer me-1" aria-hidden="true"></i> Print Quotation
                </button>
                @if($quotation->status === 'accepted')
                    <form method="POST" action="{{ route('quotations.convert', $quotation) }}" class="d-inline m-0 p-0">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm px-3">
                            <i class="bx bx-transfer me-1" aria-hidden="true"></i> Convert to Invoice
                        </button>
                    </form>
                @endif
                <a href="{{ route('quotations.download', $quotation) }}" class="btn btn-primary btn-sm px-4">
                    <i class="bx bx-download me-1" aria-hidden="true"></i> Download PDF
                </a>
            </div>
        </div>
    </nav>

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

    <div class="quotation-preview-shell">
        @include('quotations.partials.document')
    </div>
@endsection
