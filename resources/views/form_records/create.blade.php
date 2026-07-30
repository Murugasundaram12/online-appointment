@extends('layouts.app')

@section('title', 'Create Form Record')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <h2 class="fs-4 m-0 fw-bold">Create form record</h2>
    </nav>
    <div class="container-fluid px-4 pt-4">
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('form-records.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Form</label>
                            <select class="form-select" name="form_id" required>
                                @foreach($forms as $form)
                                    <option value="{{ $form->id }}">{{ $form->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Client</label>
                            <select class="form-select" name="client_id" required>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="submitted_data[notes]" rows="4">{{ old('submitted_data.notes') }}</textarea>
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
