@extends('layouts.app')

@section('title', 'Edit Form')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">Edit Form</h2>
            <a href="{{ route('forms.index') }}" class="btn btn-white border btn-sm text-muted">Back to List</a>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-4">
                <form action="{{ route('forms.update', $form->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label">Form Name <span class="required-mark">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ $form->name }}"
                                required>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description"
                                rows="3">{{ $form->description }}</textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ $form->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Publish Form
                                </label>
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary px-4">Update Form</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
