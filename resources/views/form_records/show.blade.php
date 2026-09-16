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
                @php
                    $submittedData = $formRecord->submitted_data;
                    if (is_string($submittedData)) {
                        $decoded = json_decode($submittedData, true);
                        if (is_string($decoded)) {
                            $decoded = json_decode($decoded, true);
                        }
                        $submittedData = is_array($decoded) ? $decoded : [];
                    } elseif (!is_array($submittedData) && !is_object($submittedData)) {
                        $submittedData = [];
                    }
                @endphp
                @forelse($submittedData as $key => $value)
                    <p><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ is_array($value) ? implode(', ', $value) : $value }}</p>
                @empty
                    <p class="text-muted">No submitted data recorded.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
