@extends('layouts.app')

@section('title', 'Add Category')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">Add Service Category</h2>
            <a href="{{ route('categories.index') }}" class="btn btn-white border btn-sm text-muted">Back to List</a>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-4">
                <form action="{{ route('categories.store') }}" method="POST" novalidate>
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Category Name <span class="required-mark">*</span></label>
                            @error('name')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Massage Therapy, Physiotherapy" required>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            @error('description')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Enter category description">{{ old('description') }}</textarea>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary px-4">Save Category</button>
                            <a href="{{ route('categories.index') }}" class="btn btn-light border ms-2">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
