@extends('layouts.app')

@section('title', 'Service Categories')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Service Categories</h1>
                <p class="text-muted mb-0">Manage service category groups and classifications.</p>
            </div>
            <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm px-4">
                <i class="bx bx-plus me-1"></i>Add Category
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th scope="col" class="ps-4 py-3 border-0">Category Name</th>
                                <th scope="col" class="py-3 border-0">Description</th>
                                <th scope="col" class="py-3 border-0">Services Count</th>
                                <th scope="col" class="pe-4 py-3 border-0 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @forelse($categories as $category)
                                <tr>
                                    <td class="ps-4 py-3 fw-semibold text-dark">{{ $category->name }}</td>
                                    <td class="text-muted small">{{ $category->description ?: '-' }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $category->services_count }} services</span></td>
                                    <td class="pe-4 text-end">
                                        <a href="{{ route('categories.show', $category) }}" class="btn btn-link text-muted p-0 me-2" title="View">
                                            <i class="bx bx-show"></i>
                                        </a>
                                        <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-link text-muted p-0 me-2" title="Edit">
                                            <i class="bx bx-pencil"></i>
                                        </a>
                                        <form action="{{ route('categories.destroy', $category->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link text-muted p-0" title="Delete">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No categories found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @include('partials.pagination', ['paginator' => $categories])
        </div>
    </div>
@endsection
