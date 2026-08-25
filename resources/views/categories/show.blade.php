@extends('layouts.app')

@section('title', $category->name)

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">{{ $category->name }}</h1>
                <p class="text-muted mb-0">{{ $category->description ?: 'No description has been added.' }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('categories.index') }}" class="btn btn-light border">Back to list</a>
                <a href="{{ route('categories.edit', $category) }}" class="btn btn-primary">Edit category</a>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3"><strong>Services in this category</strong></div>
            <div class="list-group list-group-flush">
                @forelse($category->services as $service)
                    <a href="{{ route('services.show', $service) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>{{ $service->name }}</span>
                        <span class="text-muted">${{ number_format((float) $service->price, 2) }} · {{ $service->duration_minutes }} min</span>
                    </a>
                @empty
                    <div class="list-group-item text-muted">No services are assigned to this category.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
