@extends('layouts.app')

@section('title', 'Locations')

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
            <form method="GET" action="{{ route('locations.index') }}">
                <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                <input type="text" name="search" value="{{ request('search') }}" class="search-input" placeholder="Search">
            </form>
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
            @include('partials.pagination', ['paginator' => $locations])
        </div>
    </div>
@endsection
