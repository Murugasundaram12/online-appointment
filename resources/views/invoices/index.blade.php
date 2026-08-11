@extends('layouts.app')

@section('title', 'Invoices')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Invoices</h1>
                <p class="text-muted mb-0">Track billed amounts, payments and invoice status.</p>
            </div>
        </div>

        <!-- Toolbar -->
        <x-list-toolbar :paginator="$invoices" searchAction="{{ route('invoices.index') }}" searchPlaceholder="Search invoices">
            <x-slot name="filters">
                <x-list-toolbar-filters
                    :showClear="(request()->has('search') && request('search') !== '') || request()->filled('status')"
                    :clearUrl="route('invoices.index', ['per_page' => request('per_page', $invoices->perPage())])" />
                <div class="dropdown">
                    <button class="btn btn-light border dropdown-toggle btn-sm text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ request()->filled('status') ? ucwords(str_replace('_', ' ', request('status'))) : 'Status' }}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('invoices.index', request()->except(['status', 'page'])) }}">All</a></li>
                        <li><a class="dropdown-item" href="{{ route('invoices.index', array_merge(request()->except(['status', 'page']), ['status' => 'outstanding'])) }}">Outstanding</a></li>
                        <li><a class="dropdown-item" href="{{ route('invoices.index', array_merge(request()->except(['status', 'page']), ['status' => 'paid'])) }}">Paid</a></li>
                        <li><a class="dropdown-item" href="{{ route('invoices.index', array_merge(request()->except(['status', 'page']), ['status' => 'partially_paid'])) }}">Partially paid</a></li>
                        <li><a class="dropdown-item" href="{{ route('invoices.index', array_merge(request()->except(['status', 'page']), ['status' => 'void'])) }}">Void</a></li>
                    </ul>
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm px-4"><i class="bx bx-plus-circle me-1"></i>Create Invoice</a>
            </x-slot>
        </x-list-toolbar>

        <!-- Table -->
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th scope="col" class="ps-4 py-3 border-0">Invoice date <i class='bx bx-down-arrow-alt'></i>
                                </th>
                                <th scope="col" class="py-3 border-0">Client name</th>
                                <th scope="col" class="py-3 border-0">Status</th>
                                <th scope="col" class="py-3 border-0">Invoice number</th>
                                <th scope="col" class="py-3 border-0 text-end">Total price</th>
                                <th scope="col" class="py-3 border-0 text-end">Total paid</th>
                                <th scope="col" class="py-3 border-0">Provider name</th>
                                <th scope="col" class="pe-4 py-3 border-0 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @forelse($invoices as $invoice)
                                <tr>
                                    <td class="ps-4 py-3 text-muted small">{{ $invoice->issued_date->format('M j, Y') }} -
                                        {{ $invoice->created_at->format('h:i A') }}
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-initials bg-light-danger text-danger me-2 rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 25px; height: 25px; font-size: 0.7rem;">
                                                <i class='bx bx-user'></i>
                                            </div>
                                            <span class="small text-dark">{{ $invoice->client->name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($invoice->status == 'outstanding')
                                            <div
                                                class="d-inline-flex align-items-center px-2 py-1 rounded small fw-500 bg-outstanding-soft text-outstanding border border-danger-subtle">
                                                <i class='bx bx-receipt me-1'></i> Outstanding
                                            </div>
                                        @elseif($invoice->status == 'paid')
                                            <div
                                                class="d-inline-flex align-items-center px-2 py-1 rounded small fw-500 bg-paid-soft text-paid border border-success-subtle">
                                                <i class='bx bx-receipt me-1'></i> Paid
                                            </div>
                                        @else
                                            <div
                                                class="d-inline-flex align-items-center px-2 py-1 rounded small fw-500 bg-light text-muted border">
                                                {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $invoice->invoice_number }}</td>
                                    <td class="small fw-500 text-end">${{ number_format($invoice->total_amount, 2) }}</td>
                                    <td class="text-muted small text-end">${{ number_format($invoice->paid_amount, 2) }}</td>
                                    <td class="small">{{ $invoice->staff->name }}</td>
                                    <td class="pe-4 text-end">
                                        <a href="{{ route('invoices.show', $invoice->id) }}"
                                            class="btn btn-link text-muted p-0 me-2"><i class='bx bx-show'></i></a>
                                        <a href="{{ route('invoices.download', $invoice->id) }}"
                                            class="btn btn-link text-muted p-0"><i class='bx bx-download'></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No invoices found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Pagination -->
            @include('partials.pagination', ['paginator' => $invoices])
        </div>
    </div>
@endsection
