@extends('layouts.app')

@section('title', isset($location) ? 'Edit Location' : 'Add Location')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">{{ isset($location) ? 'Edit Location' : 'Add Location' }}</h2>
            <a href="{{ route('locations.index') }}" class="btn btn-white border btn-sm text-muted">Back to List</a>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-4">
                <form action="{{ isset($location) ? route('locations.update', $location->id) : route('locations.store') }}" method="POST" novalidate>
                    @csrf
                    @if(isset($location))
                        @method('PUT')
                    @endif
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Location Name <span class="required-mark">*</span></label>
                            @error('name')
                                <div class="invalid-feedback d-block text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $location->name ?? '') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            @error('email')
                                <div class="invalid-feedback d-block text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $location->email ?? '') }}">
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            @error('phone')
                                <div class="invalid-feedback d-block text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $location->phone ?? '') }}">
                        </div>

                        <div class="col-md-6">
                            <label for="color" class="form-label">Color Theme</label>
                            @error('color')
                                <div class="invalid-feedback d-block text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="color" class="form-control form-control-color w-100 @error('color') is-invalid @enderror" id="color" name="color" value="{{ old('color', $location->color ?? '#4f46e5') }}" style="height: 42px; padding: 6px;">
                        </div>

                        <div class="col-md-6">
                            <label for="timezone" class="form-label">Timezone</label>
                            @error('timezone')
                                <div class="invalid-feedback d-block text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <select class="form-select @error('timezone') is-invalid @enderror" id="timezone" name="timezone">
                                @foreach(timezone_identifiers_list() as $timezone)
                                    <option value="{{ $timezone }}" {{ old('timezone', $location->timezone ?? 'UTC') === $timezone ? 'selected' : '' }}>{{ $timezone }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 d-flex align-items-center mt-5">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $location->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="is_active">Location is active and available for bookings</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            @error('address')
                                <div class="invalid-feedback d-block text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address', $location->address ?? '') }}</textarea>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary px-4">{{ isset($location) ? 'Save Changes' : 'Add Location' }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
