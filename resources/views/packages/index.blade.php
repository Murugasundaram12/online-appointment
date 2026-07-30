@extends('layouts.app')

@section('title', 'Packages List')

@push('styles')
    <style>
        .badge-upgrade {
            background-color: #fff1f2;
            color: #f64e60;
            font-size: 0.75rem;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-weight: 500;
            margin-left: 10px;
        }

        .filter-select {
            background-color: #f5f8fa;
            border: none;
            font-size: 0.85rem;
            font-weight: 500;
            color: #3f4254;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .search-input {
            background-color: #f5f8fa;
            border: none;
            border-radius: 8px;
            padding-left: 40px;
        }

        .search-container {
            position: relative;
        }

        .search-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a1a5b7;
        }

        .table thead th {
            background-color: #fcfdfe;
            color: #7e8299;
            font-weight: 600;
            font-size: 0.85rem;
            border-top: none;
            padding: 1.25rem 1rem;
        }

        .empty-state {
            padding: 5rem 1rem;
            text-align: center;
        }

        .empty-state i {
            font-size: 3rem;
            color: #3699ff;
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #3f4254;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #b5b5c3;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
    </style>
@endpush

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-transparent py-4 px-4">
        <div class="d-flex align-items-center justify-content-between w-100">
            <div class="d-flex align-items-center">
                <h2 class="fs-4 m-0 fw-bold">Packages list</h2>
                <span class="badge-upgrade">Upgrade plan</span>
            </div>
            <a href="{{ route('packages.create') }}" class="btn btn-primary px-4 fw-500">Add new package</a>
        </div>
    </nav>

    <div class="container-fluid px-4">

        <!-- Filters -->
        <div class="d-flex flex-wrap gap-3 align-items-center mb-4">
            <div class="search-container flex-grow-1" style="max-width: 400px;">
                <i class='bx bx-search'></i>
                <input type="text" class="form-control search-input" placeholder="Search">
            </div>
            <div class="dropdown">
                <button class="btn filter-select dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Status
                </button>
            </div>
        </div>

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
                                <th scope="col" class="py-3 border-0">Package price</th>
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
                                    <td class="text-muted small">${{ number_format($package->price, 2) }}</td>
                                    <td class="text-muted small">{{ $package->services->sum(fn($service) => $service->pivot->quantity) }}</td>
                                    <td class="text-muted small">0</td>
                                    <td class="pe-4 text-end">
                                        <a href="{{ route('packages.edit', $package->id) }}"
                                            class="btn btn-link text-muted p-0 me-2"><i class='bx bx-pencil'></i></a>
                                        <form action="{{ route('packages.destroy', $package->id) }}" method="POST"
                                            class="d-inline" onsubmit="return confirm('Are you sure?');">
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
            <div class="card-footer bg-white border-0 py-3 px-4 d-flex justify-content-end">
                {{ $packages->links() }}
            </div>
        </div>
    </div>
@endsection
