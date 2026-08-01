@extends('layouts.app')

@section('title', 'Edit Service')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">Edit Service</h2>
            <a href="{{ route('services.index') }}" class="btn btn-white border btn-sm text-muted">Back to List</a>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-4">
                <form action="{{ route('services.update', $service->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Service Name <span class="required-mark">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $service->name) }}"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label for="service_category_id" class="form-label">Category</label>
                            <select class="form-select" id="service_category_id" name="service_category_id">
                                <option value="">Select Category</option>
                                <!-- populate from categories -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="type" class="form-label">Service Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="in_person" {{ old('type', $service->type) == 'in_person' ? 'selected' : '' }}>In-Person
                                </option>
                                <option value="online" {{ old('type', $service->type) == 'online' ? 'selected' : '' }}>Online</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="price" class="form-label">Price <span class="required-mark">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price"
                                value="{{ old('price', $service->price) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="duration_minutes" class="form-label">Duration (minutes) <span class="required-mark">*</span></label>
                            <input type="number" class="form-control" id="duration_minutes" name="duration_minutes"
                                value="{{ old('duration_minutes', $service->duration_minutes) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="buffer_minutes" class="form-label">Buffer Time (minutes)</label>
                            <input type="number" class="form-control" id="buffer_minutes" name="buffer_minutes"
                                value="{{ old('buffer_minutes', $service->buffer_minutes) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="color" class="form-label">Color Label</label>
                            <input type="color" class="form-control form-control-color" id="color" name="color"
                                value="{{ old('color', $service->color ?? '#3699ff') }}">
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description"
                                rows="3">{{ old('description', $service->description) }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $service->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active Service
                                </label>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary px-4">Update Service</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
