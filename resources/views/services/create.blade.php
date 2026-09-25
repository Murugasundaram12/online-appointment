@extends('layouts.app')

@section('title', 'Add Service')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">Add Service</h2>
            <a href="{{ route('services.index') }}" class="btn btn-white border btn-sm text-muted">Back to List</a>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-4">
                <form action="{{ route('services.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Service Name <span class="required-mark">*</span></label>
                            @error('name')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="service_category_id" class="form-label">Category <span class="required-mark">*</span></label>
                            @error('service_category_id')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <select class="form-select @error('service_category_id') is-invalid @enderror" id="service_category_id" name="service_category_id" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('service_category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="type" class="form-label">Service Type</label>
                            @error('type')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type">
                                <option value="in_person" {{ old('type') == 'in_person' ? 'selected' : '' }}>In-Person</option>
                                <option value="online" {{ old('type') == 'online' ? 'selected' : '' }}>Online</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="price" class="form-label">Price <span class="required-mark">*</span></label>
                            @error('price')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="duration_minutes" class="form-label">Duration (minutes) <span class="required-mark">*</span></label>
                            @error('duration_minutes')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="number" class="form-control @error('duration_minutes') is-invalid @enderror" id="duration_minutes" name="duration_minutes"
                                value="{{ old('duration_minutes') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="buffer_minutes" class="form-label">Buffer Time (minutes)</label>
                            @error('buffer_minutes')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="number" class="form-control @error('buffer_minutes') is-invalid @enderror" id="buffer_minutes" name="buffer_minutes" value="{{ old('buffer_minutes', 0) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="color" class="form-label">Color Label</label>
                            @error('color')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror" id="color" name="color"
                                value="{{ old('color', '#6366f1') }}">
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            @error('description')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        </div>
                        <div class="col-12">
                            @error('is_active')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                    {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active Service
                                </label>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary px-4">Save Service</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
