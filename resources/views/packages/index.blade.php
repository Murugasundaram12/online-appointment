@extends('layouts.app')

@section('title', 'Packages List')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Packages</h1>
                <p class="text-muted mb-0">Bundle services into packages for your clients.</p>
            </div>
        </div>

        <!-- Toolbar -->
        <x-list-toolbar :paginator="$packages" searchAction="{{ route('packages.index') }}" searchPlaceholder="Search packages">
            <x-slot name="filters">
                <x-list-toolbar-filters
                    :showClear="request()->has('search') && request('search') !== '' || request()->filled('status')"
                    :clearUrl="route('packages.index', ['per_page' => request('per_page', $packages->perPage())])" />
                <div class="dropdown">
                    <button class="btn btn-light border dropdown-toggle btn-sm text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ request()->filled('status') ? ucfirst(request('status')) : 'Status' }}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('packages.index', request()->except(['status', 'page'])) }}">All</a></li>
                        <li><a class="dropdown-item" href="{{ route('packages.index', array_merge(request()->except(['status', 'page']), ['status' => 'active'])) }}">Active</a></li>
                        <li><a class="dropdown-item" href="{{ route('packages.index', array_merge(request()->except(['status', 'page']), ['status' => 'inactive'])) }}">Inactive</a></li>
                    </ul>
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('packages.create') }}" class="btn btn-primary btn-sm px-4"><i class="bx bx-plus me-1"></i>Add New Package</a>
            </x-slot>
        </x-list-toolbar>

        <!-- Table -->
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 table-hover">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th scope="col" class="ps-4 py-3 border-0" style="min-width: 250px;">Package name <i
                                        class='bx bx-up-arrow-alt'></i></th>
                                <th scope="col" class="py-3 border-0">Status</th>
                                <th scope="col" class="py-3 border-0 text-end">Package price</th>
                                <th scope="col" class="py-3 border-0">No.sessions</th>
                                <th scope="col" class="py-3 border-0">Sold records</th>
                                <th scope="col" class="pe-4 py-3 border-0 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @forelse($packages as $package)
                                <tr>
                                    <td class="ps-4 py-3 fw-medium text-dark">{{ $package->name }}</td>
                                    <td>
                                        @if($package->is_active)
                                            <span class="badge bg-light-success text-success">Active</span>
                                        @else
                                            <span class="badge bg-light-secondary text-muted">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small text-end">${{ number_format($package->price, 2) }}</td>
                                    <td class="text-muted small">{{ $package->services->sum(fn($service) => $service->pivot->quantity) }}</td>
                                    <td class="text-muted small">0</td>
                                    <td class="pe-4 text-end">
                                        <a href="{{ route('packages.edit', $package->id) }}"
                                            class="btn btn-link text-muted p-0 me-2"><i class='bx bx-pencil'></i></a>
                                        <form action="{{ route('packages.destroy', $package->id) }}" method="POST"
                                            class="d-inline"
                                            data-confirm="This package will be removed if the server allows it."
                                            data-confirm-title="Delete package?"
                                            data-confirm-record="{{ $package->name }}"
                                            data-confirm-text="Delete"
                                            data-confirm-loading="Deleting...">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link text-muted p-0"><i
                                                    class='bx bx-trash'></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class='bx bx-file-blank fs-1 text-muted mb-3'></i>
                                        <h3 class="fs-6 fw-bold text-dark">No Packages found</h3>
                                        <p class="text-muted small">Try adjusting your search or creating a new one.</p>
                                        <a href="{{ route('packages.create') }}" class="btn btn-primary px-4 btn-sm">Add new
                                            package</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Pagination -->
            @include('partials.pagination', ['paginator' => $packages])
        </div>
    </div>
@endsection
