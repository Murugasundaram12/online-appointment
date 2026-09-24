@extends('layouts.app')

@section('title', 'Create Form Record')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <h2 class="fs-4 m-0 fw-bold">Create form record</h2>
    </nav>
    <div class="container-fluid px-4 pt-4">
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('form-records.store') }}" novalidate>
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Form <span class="required-mark">*</span></label>
                            @error('form_id')
                                <div class="invalid-feedback d-block text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <select class="form-select @error('form_id') is-invalid @enderror" name="form_id" required>
                                @foreach($forms as $form)
                                    <option value="{{ $form->id }}">{{ $form->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Client <span class="required-mark">*</span></label>
                            @error('client_id')
                                <div class="invalid-feedback d-block text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <select class="form-select @error('client_id') is-invalid @enderror" name="client_id" required>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            @error('submitted_data.notes')
                                <div class="invalid-feedback d-block text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            @error('submitted_data')
                                <div class="invalid-feedback d-block text-danger small mb-1 fw-medium" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror
                            <textarea class="form-control @error('submitted_data.notes') is-invalid @enderror" name="submitted_data[notes]" rows="4">{{ old('submitted_data.notes') }}</textarea>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary px-4">Save record</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
