@extends('layouts.app')

@section('title', 'Forms')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-transparent py-4 px-4">
        <div class="d-flex align-items-center justify-content-between w-100">
            <h2 class="fs-4 m-0 fw-bold">Forms</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('forms.create') }}" class="btn btn-primary px-4 fw-500">Create form</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">

        <!-- Filters -->
        <div class="d-flex flex-wrap gap-3 align-items-center mb-4">
            <div class="search-container flex-grow-1" style="max-width: 400px;">
                <i class='bx bx-search'></i>
                <form method="GET" action="{{ route('forms.index') }}">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control search-input" placeholder="Search">
                </form>
            </div>
            <div class="dropdown">
                <button class="btn filter-select dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Status
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('forms.index', request()->except(['status', 'page'])) }}">All</a></li>
                    <li><a class="dropdown-item" href="{{ route('forms.index', array_merge(request()->except(['status', 'page']), ['status' => 'active'])) }}">Published</a></li>
                    <li><a class="dropdown-item" href="{{ route('forms.index', array_merge(request()->except(['status', 'page']), ['status' => 'inactive'])) }}">Draft</a></li>
                </ul>
            </div>
        </div>
        <!-- Table -->
        <div class="bg-white rounded shadow-sm overflow-hidden mb-5">

            <div class="d-flex justify-content-end p-3 bg-white border-bottom">
                <i class='bx bx-hide text-muted fs-5'></i>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th style="min-width: 300px;">Form name</th>
                            <th>Status</th>
                            <th>Category</th>
                            <th>Last updated <i class='bx bx-down-arrow-alt'></i></th>
                            <th>Records</th>
                            <th>Tags</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($forms as $form)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="form-icon-box">
                                            <i class='bx bx-user-voice fs-5'></i>
                                        </div>
                                        {{ $form->name }}
                                    </div>
                                </td>
                                <td>
                                    @if($form->is_active)
                                        <span class="status-dropdown badge-published">Published</span>
                                    @else
                                        <span class="status-dropdown badge-draft">Draft</span>
                                    @endif
                                </td>
                                <td class="text-muted small">-</td>
                                <td class="text-muted small">{{ $form->updated_at->format('M j, Y') }}</td>
                                <td class="text-muted small">0</td>
                                <td class="text-muted small">-</td>
                                <td class="text-end">
                                    <a href="{{ route('forms.edit', $form->id) }}" class="btn btn-link text-muted p-0 me-2"><i
                                            class='bx bx-pencil'></i></a>
                                    <form action="{{ route('forms.destroy', $form->id) }}" method="POST" class="d-inline"
                                        data-confirm="This form will be deleted if the server allows it."
                                        data-confirm-title="Delete form?"
                                        data-confirm-record="{{ $form->name }}"
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
                                <td colspan="7" class="text-center py-5 text-muted">No forms found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>
            @include('partials.pagination', ['paginator' => $forms])
        </div>
    </div>
@endsection
