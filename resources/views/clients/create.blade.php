@extends('layouts.app')

@section('title', 'Add Client')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">Add New Client</h2>
            <a href="{{ route('clients.index') }}" class="btn btn-white border btn-sm text-muted">Back to List</a>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-4">
                <form action="{{ route('clients.store') }}" method="POST">
                    @csrf
                    @include('clients.partials.form-fields')
                    <div class="row">
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary px-4">Save Client</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
