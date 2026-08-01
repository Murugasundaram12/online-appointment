@extends('layouts.app')

@section('title', 'Edit Package')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">Edit Package</h2>
            <a href="{{ route('packages.index') }}" class="btn btn-white border btn-sm text-muted">Back to List</a>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-4">
                <form action="{{ route('packages.update', $package->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Package Name <span class="required-mark">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $package->name) }}"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label for="price" class="form-label">Price <span class="required-mark">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price"
                                value="{{ old('price', $package->price) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="validity_days" class="form-label">Validity (Days)</label>
                            <input type="number" class="form-control" id="validity_days" name="validity_days"
                                value="{{ old('validity_days', $package->validity_days) }}">
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description"
                                rows="3">{{ old('description', $package->description) }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Included Services</label>
                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                @php($selectedServices = $package->services->keyBy('id'))
                                @forelse($services as $service)
                                    @php($selected = $selectedServices->get($service->id))
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <input class="form-check-input" type="checkbox" name="services[{{ $service->id }}][selected]" value="1" id="service_{{ $service->id }}" {{ $selected ? 'checked' : '' }}>
                                        <label class="form-check-label flex-grow-1" for="service_{{ $service->id }}">{{ $service->name }} (${{ number_format($service->price, 2) }})</label>
                                        <input type="number" class="form-control form-control-sm" name="services[{{ $service->id }}][quantity]" value="{{ $selected ? $selected->pivot->quantity : 1 }}" min="1" style="width:90px">
                                    </div>
                                @empty
                                    <p class="text-muted small mb-0">No active services available.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $package->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active Package
                                </label>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary px-4">Update Package</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
