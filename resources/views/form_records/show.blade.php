@extends('layouts.app')

@section('title', 'Form Record')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between w-100">
            <h2 class="fs-4 m-0 fw-bold">Form record</h2>
            <a href="{{ route('form-records.index') }}" class="btn btn-white border btn-sm">Back</a>
        </div>
    </nav>
    <div class="container-fluid px-4 pt-4">
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-4">
                <p><strong>Form:</strong> {{ optional($formRecord->form)->name }}</p>
                <p><strong>Client:</strong> {{ optional($formRecord->client)->name }}</p>
                <p><strong>Submitted:</strong> {{ optional($formRecord->submitted_at)->format('M j, Y g:i A') }}</p>
                <hr>
                @foreach(($formRecord->submitted_data ?? []) as $key => $value)
                    <p><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ is_array($value) ? implode(', ', $value) : $value }}</p>
                @endforeach
            </div>
        </div>
    </div>
@endsection
