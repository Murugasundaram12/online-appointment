@extends(auth('staff')->check() ? 'layouts.app' : 'layouts.public')

@section('title', '403 - Access Denied')

@section('content')
<div class="container-fluid px-4 py-5 d-flex align-items-center justify-content-center" style="min-height: 60vh;">
    <div class="d-none" data-app-alert-type="danger" data-app-alert-title="Error" data-app-alert-message="{{ $exception->getMessage() ?: 'Unauthorized action.' }}"></div>
    <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 text-center" style="max-width: 480px; width: 100%;">
        <div class="avatar-initials bg-light-danger text-danger mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; font-size: 1.75rem;">
            <i class="bx bx-shield-x"></i>
        </div>
        <h3 class="fw-bold text-dark mb-1">Access Denied</h3>
        <p class="text-secondary small mb-4">{{ $exception->getMessage() ?: 'You do not have permission to access this page or perform this action.' }}</p>
        <div class="d-flex justify-content-center gap-2">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="btn btn-primary btn-sm px-4">
                <i class="bx bx-arrow-back me-1"></i> Go Back
            </a>
            @auth('staff')
                <a href="{{ route('dashboard') }}" class="btn btn-light btn-sm px-3">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-light btn-sm px-3">Login</a>
            @endauth
        </div>
    </div>
</div>
@endsection
