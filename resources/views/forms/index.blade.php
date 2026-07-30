@extends('layouts.app')

@section('title', 'Forms')

@push('styles')
    <style>
        .form-icon-box {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            color: #7e8299;
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

        .table tbody td {
            padding: 1.25rem 1rem;
            vertical-align: middle;
            font-size: 0.9rem;
            color: #3f4254;
            border-bottom: 1px solid #f8f9fa;
        }

        .btn-outline-custom {
            border: 1px solid #eef0f7;
            color: #3699ff;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .btn-outline-custom:hover {
            background-color: #f1f6ff;
            color: #3699ff;
        }

        .badge-draft {
            background-color: #f1f3f9;
            color: #7e8299;
            border: 1px solid #eef0f7;
        }

        .badge-published {
            background-color: #e8fff3;
            color: #50cd89;
            border: 1px solid #d1f7e4;
        }

        .status-dropdown {
            font-size: 0.75rem;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
    </style>
@endpush

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-transparent py-4 px-4">
        <div class="d-flex align-items-center justify-content-between w-100">
            <h2 class="fs-4 m-0 fw-bold">Forms</h2>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-custom px-4">Category manager</button>
                    <a href="{{ route('forms.create') }}" class="btn btn-primary px-4 fw-500 dropdown-toggle" type="button"
                        data-bs-toggle="dropdown">Create form</a>
                </div>
            </div>
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
                    Category
                </button>
            </div>
            <div class="dropdown">
                <button class="btn filter-select dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Status
                </button>
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
            <div class="card-footer bg-white border-0 py-3 px-4 d-flex justify-content-end">
                {{ $forms->links() }}
            </div>
        </div>
    </div>
@endsection
