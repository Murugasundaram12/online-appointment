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
                <a href="{{ route('quotations.index') }}" class="btn btn-white border btn-sm text-muted">Back to
                    quotations</a>
                <a href="{{ route('quotations.create') }}" class="btn btn-white border btn-sm text-muted">
                    <i class="bx bx-plus me-1" aria-hidden="true"></i> Create New Quotation
                </a>
                @if($quotation->status === 'draft')
                    <a href="{{ route('quotations.edit', $quotation) }}" class="btn btn-white border btn-sm">
                        <i class="bx bx-edit me-1" aria-hidden="true"></i> Edit Quotation
                    </a>
                @endif
                @if(in_array($quotation->status, ['draft', 'sent'], true))
                    <form method="POST" action="{{ route('quotations.accept', $quotation) }}" class="d-inline m-0 p-0 no-print">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm px-3">
                            <i class="bx bx-check-circle me-1" aria-hidden="true"></i> Approve Quotation
                        </button>
                    </form>
                @endif
                @if(in_array($quotation->status, ['draft', 'sent', 'accepted'], true) && $quotation->status !== 'converted' && !$quotation->invoices()->exists())
                    <button type="button" class="btn btn-outline-danger btn-sm px-3" data-bs-toggle="modal"
                        data-bs-target="#cancelQuotationModal" id="openCancelQuotationModalBtn">
                        <i class="bx bx-x-circle me-1" aria-hidden="true"></i> Cancel Quotation
                    </button>
                @endif
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
        <div class="alert alert-success alert-dismissible fade show m-3 no-print" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show m-3 no-print" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="quotation-preview-shell">
        @include('quotations.partials.document')
    </div>

    @if(in_array($quotation->status, ['draft', 'sent', 'accepted'], true) && $quotation->status !== 'converted' && !$quotation->invoices()->exists())
        <div class="modal fade app-modal no-print" id="cancelQuotationModal" tabindex="-1"
            aria-labelledby="cancelQuotationModalTitle" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <div class="modal-heading d-flex align-items-center gap-3">
                            <div class="modal-icon"
                                style="background: var(--warning-soft, #fffbeb); color: var(--warning, #f59e0b);"
                                aria-hidden="true">
                                <i class="bx bx-error-circle fs-4"></i>
                            </div>
                            <div>
                                <h5 class="modal-title mb-0" id="cancelQuotationModalTitle">Cancel Quotation</h5>
                                <p class="modal-subtitle text-muted mb-0 small">Please review before continuing.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-4">
                        <p class="mb-0 text-secondary" style="font-size: 0.95rem; line-height: 1.5;">
                            Are you sure you want to cancel quotation <strong
                                class="text-dark fw-semibold">{{ $quotation->quotation_number }}</strong>?
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border px-3" data-bs-dismiss="modal"
                            id="cancelQuotationDismissBtn">
                            Keep Quotation
                        </button>
                        <form id="cancel-quotation-form" method="POST" action="{{ route('quotations.cancel', $quotation) }}"
                            class="d-inline m-0 p-0">
                            @csrf
                            <button type="submit" class="btn btn-danger px-3" id="confirmCancelQuotationBtn">
                                <i class="bx bx-x-circle me-1" aria-hidden="true"></i> Yes, Cancel Quotation
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cancelForm = document.getElementById('cancel-quotation-form');
            const confirmBtn = document.getElementById('confirmCancelQuotationBtn');
            const dismissBtn = document.getElementById('cancelQuotationDismissBtn');
            let isSubmitting = false;

            if (cancelForm && confirmBtn) {
                cancelForm.addEventListener('submit', function (e) {
                    if (isSubmitting) {
                        e.preventDefault();
                        return false;
                    }
                    isSubmitting = true;
                    confirmBtn.disabled = true;
                    if (dismissBtn) {
                        dismissBtn.disabled = true;
                    }
                    confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Cancelling...';
                });
            }
        });
    </script>
@endpush