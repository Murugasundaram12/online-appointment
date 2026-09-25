@extends('layouts.app')

@section('title', 'Add Staff')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">Add Staff Member</h2>
            <a href="{{ route('staff.index') }}" class="btn btn-white border btn-sm text-muted">Back to List</a>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-4">
                <form action="{{ route('staff.store') }}" method="POST" novalidate>
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name</label>
                            @error('name')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email <span class="required-mark">*</span></label>
                            @error('email')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            @error('phone')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="(xxx) xxx-xxxx">
                        </div>
                        <input type="hidden" name="access_level" value="staff">
                        <div class="col-md-6">
                            <label for="registration_number" class="form-label">Registration Number</label>
                            @error('registration_number')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="text" class="form-control @error('registration_number') is-invalid @enderror" id="registration_number" name="registration_number" value="{{ old('registration_number') }}" placeholder="e.g. RMT-123456">
                        </div>
                        <div class="col-md-6">
                            <label for="designation" class="form-label">Designation</label>
                            @error('designation')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <input type="text" class="form-control @error('designation') is-invalid @enderror" id="designation" name="designation" value="{{ old('designation') }}" placeholder="e.g. Senior Practitioner">
                        </div>
                        <div class="col-md-6">
                            <label for="location_id" class="form-label">Location</label>
                            @error('location_id')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <select class="form-select @error('location_id') is-invalid @enderror" id="location_id" name="location_id">
                                <option value="">No location assigned</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="category" class="form-label">Category</label>
                            @error('category')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <select class="form-select @error('category') is-invalid @enderror" id="category" name="category">
                                <option value="" selected>Select Category</option>
                                @foreach($serviceCategories ?? $categories ?? [] as $categoryOption)
                                    @php $catName = is_string($categoryOption) ? $categoryOption : $categoryOption->name; @endphp
                                    <option value="{{ $catName }}" {{ old('category') == $catName ? 'selected' : '' }}>
                                        {{ $catName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="createStaffPassword" class="form-label">Password</label>
                            @error('password')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="input-group">
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="createStaffPassword" name="password" placeholder="Enter password (optional)" autocomplete="new-password">
                                <button class="btn btn-outline-secondary js-toggle-password-btn" type="button"
                                    id="toggleCreateStaffPassword" data-target="#createStaffPassword"
                                    aria-label="Show password"><i class="bx bx-show"></i></button>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="bio" class="form-label">Bio / Notes</label>
                            @error('bio')
                                <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <textarea class="form-control @error('bio') is-invalid @enderror" id="bio" name="bio" rows="3">{{ old('bio') }}</textarea>
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
                                    Active Account
                                </label>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary px-4">Save Staff</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
