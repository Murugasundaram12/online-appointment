@extends('layouts.app')

@section('title', 'Add Package')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">Add New Package</h2>
            <a href="{{ route('packages.index') }}" class="btn btn-white border btn-sm text-muted">Back to List</a>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-4">
                <form action="{{ route('packages.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Package Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" value="{{ old('price') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="validity_days" class="form-label">Validity (Days)</label>
                            <input type="number" class="form-control" id="validity_days" name="validity_days" value="{{ old('validity_days') }}">
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Included Services</label>
                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                @forelse($services as $service)
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <input class="form-check-input" type="checkbox" name="services[{{ $service->id }}][selected]" value="1" id="service_{{ $service->id }}">
                                        <label class="form-check-label flex-grow-1" for="service_{{ $service->id }}">{{ $service->name }} (${{ number_format($service->price, 2) }})</label>
                                        <input type="number" class="form-control form-control-sm" name="services[{{ $service->id }}][quantity]" value="1" min="1" style="width:90px">
                                    </div>
                                @empty
                                    <p class="text-muted small mb-0">No active services available.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                    checked>
                                <label class="form-check-label" for="is_active">
                                    Active Package
                                </label>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary px-4">Save Package</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
