@extends('layouts.app')

@section('title', 'Edit Insurance Company')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">Edit Insurance Company</h2>
            <a href="{{ route('insurance-companies.index') }}" class="btn btn-white border btn-sm text-muted">Back to List</a>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="card shadow-sm border-0 rounded" style="max-width: 600px;">
            <div class="card-body p-4">
                <form action="{{ route('insurance-companies.update', $insuranceCompany->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="name" class="form-label">Company Name <span class="required-mark">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $insuranceCompany->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary px-4">Update Company</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
