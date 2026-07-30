@extends('layouts.app')

@section('title', 'Edit Staff')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">Edit Staff Member</h2>
            <a href="{{ route('staff.index') }}" class="btn btn-white border btn-sm text-muted">Back to List</a>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-4">
                <form action="{{ route('staff.update', $staff->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name <span class="required-mark">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ $staff->name }}"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address <span class="required-mark">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ $staff->email }}"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ $staff->phone }}">
                        </div>
                        <div class="col-md-6">
                            <label for="access_level" class="form-label">Access Level</label>
                            <select class="form-select" id="access_level" name="access_level">
                                <option value="staff" {{ $staff->access_level == 'staff' ? 'selected' : '' }}>Staff</option>
                                <option value="business_owner" {{ $staff->access_level == 'business_owner' ? 'selected' : '' }}>Business Owner</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="location_id" class="form-label">Location</label>
                            <select class="form-select" id="location_id" name="location_id">
                                <option value="">No location assigned</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ old('location_id', $staff->location_id) == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}{{ !$location->is_active ? ' (inactive)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="category" class="form-label">Category (e.g. RMT, Stylist)</label>
                            <input type="text" class="form-control" id="category" name="category"
                                value="{{ $staff->category }}">
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password (Leave blank to keep current)</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <div class="col-12">
                            <label for="bio" class="form-label">Bio / Notes</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3">{{ $staff->bio }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ $staff->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active Account
                                </label>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary px-4">Update Staff</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
