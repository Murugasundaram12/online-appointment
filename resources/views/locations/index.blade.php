@extends('layouts.app')

@section('title', 'Locations')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Locations</h1>
                <p class="text-muted mb-0">Manage business locations, addresses and contact details.</p>
            </div>
        </div>

        <!-- Toolbar -->
        <x-list-toolbar :paginator="$locations" searchAction="{{ route('locations.index') }}" searchPlaceholder="Search locations">
            <x-slot name="filters">
                <x-list-toolbar-filters
                    :showClear="request()->has('search') && request('search') !== ''"
                    :clearUrl="route('locations.index', ['per_page' => request('per_page', $locations->perPage())])"
                    clearLabel="Clear search" />
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('locations.create') }}" class="btn btn-primary btn-sm px-4"><i class="bx bx-plus me-1"></i>Add New Location</a>
            </x-slot>
        </x-list-toolbar>

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
