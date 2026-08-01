@extends('layouts.app')

@section('title', 'Locations')

@push('styles')
    <style>
        .search-container {
            position: relative;
            max-width: 400px;
            margin-bottom: 24px;
        }

        .search-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a1a5b7;
        }

        .search-input {
            background-color: #f5f8fa;
            border: none;
            border-radius: 8px;
            padding: 10px 15px 10px 45px;
            font-size: 0.9rem;
            width: 100%;
        }

        .table-card {
            background-color: white;
            border-radius: 12px;
            border: 1px solid #f1f3f9;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .table thead th {
            background-color: #fcfdfe;
            color: #7e8299;
            font-weight: 600;
            font-size: 0.85rem;
            border-top: none;
            padding: 1.25rem 1rem;
            border-bottom: 1px solid #f1f3f9;
        }

        .table tbody td {
            padding: 1.25rem 1rem;
            vertical-align: middle;
            font-size: 0.9rem;
            color: #3f4254;
            border-bottom: 1px solid #f8f9fa;
        }

        .location-name {
            font-weight: 600;
            color: #3f4254;
        }

        .action-icons i {
            color: #7e8299;
            cursor: pointer;
            font-size: 1.1rem;
            vertical-align: middle;
            margin-left: 10px;
        }

        .action-icons i:hover {
            color: #3699ff;
        }

        .nav-icon-box {
            width: 40px;
            height: 40px;
            background-color: #f3f6f9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7e8299;
            font-size: 1.25rem;
            position: relative;
        }

        .plus-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #3699ff;
            color: white;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
@endpush

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-transparent py-4 px-4">
        <div class="d-flex align-items-center justify-content-between w-100">
            <div class="d-flex align-items-center">
                <div class="nav-icon-box me-3">
                    <i class='bx bx-cog'></i>
                    <span class="plus-badge">+</span>
                </div>
                <div class="nav-icon-box">
                    <i class='bx bx-camera-plus'></i>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <h2 class="fs-4 m-0 fw-bold me-4">Locations</h2>
                <a href="{{ route('locations.create') }}" class="btn btn-primary px-4 fw-600">Add new location</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">

        <!-- Search -->
        <div class="search-container">
            <i class='bx bx-search'></i>
            <input type="text" class="search-input" placeholder="Search">
        </div>

        <!-- Table -->
        <div class="table-card mb-5">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Location name <i class='bx bx-up-arrow-alt'></i></th>
                            <th style="width: 30%;">Address</th>
                            <th style="width: 20%;">Public phone number</th>
                            <th class="text-end pe-4" style="width: 10%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($locations as $location)
                            <tr>
                                <td class="location-name">{{ $location->name }}</td>
                                <td class="text-muted">{{ $location->address }}</td>
                                <td>{{ $location->phone }}</td>
                                <td class="text-end pe-4 action-icons">
                                    <a href="{{ route('locations.edit', $location->id) }}" class="text-decoration-none">
                                        <i class='bx bx-pencil'></i>
                                    </a>
                                    <form action="{{ route('locations.destroy', $location->id) }}" method="POST"
                                        class="d-inline"
                                        data-confirm="This action cannot be undone. Locations used by appointments may be protected by the server."
                                        data-confirm-title="Delete location?"
                                        data-confirm-record="{{ $location->name }}"
                                        data-confirm-text="Delete"
                                        data-confirm-loading="Deleting...">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link p-0 text-muted">
                                            <i class='bx bx-trash'></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">No locations found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="card-footer bg-white border-0 py-3 px-4 d-flex justify-content-end">
                {{ $locations->links() }}
            </div>
        </div>
    </div>
@endsection
