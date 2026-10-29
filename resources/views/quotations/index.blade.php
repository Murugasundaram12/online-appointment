@extends('layouts.app')

@section('title', 'Quotations')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Quotations</h1>
                <p class="text-muted mb-0">Track estimates, client proposals and quotation status.</p>
            </div>
            <div>
                <a href="{{ route('quotations.create') }}" class="btn btn-primary px-3">
                    <i class="bx bx-plus me-1" aria-hidden="true"></i> Add Quotation
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class='bx bx-check-circle me-1'></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class='bx bx-error-circle me-1'></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Toolbar -->
        <x-list-toolbar :paginator="$quotations" searchAction="{{ route('quotations.index') }}" searchPlaceholder="Search quotations">
            <x-slot name="filters">
                <x-list-toolbar-filters
                    :showClear="(request()->has('search') && request('search') !== '') || request()->filled('status')"
                    :clearUrl="route('quotations.index', ['per_page' => request('per_page', $quotations->perPage())])" />
                <div class="dropdown">
                    <button class="btn btn-light border dropdown-toggle btn-sm text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ request()->filled('status') ? ucwords(str_replace('_', ' ', request('status'))) : 'Status' }}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('quotations.index', request()->except(['status', 'page'])) }}">All</a></li>
                        <li><a class="dropdown-item" href="{{ route('quotations.index', array_merge(request()->except(['status', 'page']), ['status' => 'draft'])) }}">Draft</a></li>
                        <li><a class="dropdown-item" href="{{ route('quotations.index', array_merge(request()->except(['status', 'page']), ['status' => 'sent'])) }}">Sent</a></li>
                        <li><a class="dropdown-item" href="{{ route('quotations.index', array_merge(request()->except(['status', 'page']), ['status' => 'accepted'])) }}">Accepted</a></li>
                        <li><a class="dropdown-item" href="{{ route('quotations.index', array_merge(request()->except(['status', 'page']), ['status' => 'rejected'])) }}">Rejected</a></li>
                        <li><a class="dropdown-item" href="{{ route('quotations.index', array_merge(request()->except(['status', 'page']), ['status' => 'expired'])) }}">Expired</a></li>
                        <li><a class="dropdown-item" href="{{ route('quotations.index', array_merge(request()->except(['status', 'page']), ['status' => 'converted'])) }}">Converted</a></li>
                    </ul>
                </div>
            </x-slot>
        </x-list-toolbar>

        <!-- Table -->
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th scope="col" class="ps-4 py-3 border-0">Issued Date</th>
                                <th scope="col" class="py-3 border-0">Client Name</th>
                                <th scope="col" class="py-3 border-0">Status</th>
                                <th scope="col" class="py-3 border-0">Quotation #</th>
                                <th scope="col" class="py-3 border-0 text-end">Total Amount</th>
                                <th scope="col" class="py-3 border-0">Valid Until</th>
                                <th scope="col" class="py-3 border-0">Provider</th>
                                <th scope="col" class="pe-4 py-3 border-0 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @forelse($quotations as $quotation)
                                <tr>
                                    <td class="ps-4 py-3 text-muted small">
                                        {{ $quotation->issued_date ? $quotation->issued_date->format('M j, Y') : '—' }}
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-initials bg-light-primary text-primary me-2 rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 25px; height: 25px; font-size: 0.7rem;">
                                                <i class='bx bx-user'></i>
                                            </div>
                                            <span class="small text-dark">{{ $quotation->client?->name ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($quotation->status === 'draft')
                                            <span class="badge bg-light text-secondary border">Draft</span>
                                        @elseif($quotation->status === 'sent')
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">Sent</span>
                                        @elseif($quotation->status === 'accepted')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Accepted</span>
                                        @elseif($quotation->status === 'rejected')
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Rejected</span>
                                        @elseif($quotation->status === 'expired')
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Expired</span>
                                        @elseif($quotation->status === 'converted')
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Converted</span>
                                        @else
                                            <span class="badge bg-light text-muted border">{{ ucfirst($quotation->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small fw-semibold">{{ $quotation->quotation_number }}</td>
                                    <td class="small fw-500 text-end">{{ $currency }}{{ number_format($quotation->total_amount, 2) }}</td>
                                    <td class="text-muted small">
                                        {{ $quotation->valid_until ? $quotation->valid_until->format('M j, Y') : '—' }}
                                    </td>
                                    <td class="small">{{ $quotation->staff?->name ?? '—' }}</td>
                                    <td class="pe-4 text-end">
                                        <div class="d-inline-flex gap-2 align-items-center">
                                            @if($quotation->status === 'draft')
                                                <a href="{{ route('quotations.edit', $quotation->id) }}"
                                                    class="btn btn-link text-muted p-0" title="Edit Quotation"><i class='bx bx-pencil'></i></a>
                                            @endif
                                            <a href="{{ route('quotations.show', $quotation->id) }}"
                                                class="btn btn-link text-muted p-0" title="View Quotation"><i class='bx bx-show fs-5'></i></a>
                                            <a href="{{ route('quotations.download', $quotation->id) }}"
                                                class="btn btn-link text-muted p-0" title="Download PDF"><i class='bx bx-download fs-5'></i></a>
                                            @if($quotation->status === 'accepted')
                                                <form method="POST" action="{{ route('quotations.convert', $quotation) }}" class="d-inline m-0 p-0" title="Convert to Invoice">
                                                    @csrf
                                                    <button type="submit" class="btn btn-link text-success p-0 border-0" style="background: transparent;">
                                                        <i class='bx bx-transfer fs-5'></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        No quotations found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Pagination -->
            @include('partials.pagination', ['paginator' => $quotations])
        </div>
    </div>
@endsection

