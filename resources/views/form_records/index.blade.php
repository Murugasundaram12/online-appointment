@extends('layouts.app')

@section('title', 'Form Records')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">Form records</h2>
            <a href="{{ route('form-records.create') }}" class="btn btn-primary btn-sm px-4">Add record</a>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">

        <!-- Filters -->
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class='bx bx-search text-muted'></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" placeholder="Search">
                </div>
            </div>
            <div class="col-md-9 d-flex flex-wrap gap-2 justify-content-end">
                <div class="dropdown">
                    <button class="btn btn-white border dropdown-toggle btn-sm text-muted" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Last updated
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Today</a></li>
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-white border dropdown-toggle btn-sm text-muted" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Appointment date
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">All</a></li>
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-white border dropdown-toggle btn-sm text-muted" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Status
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Pending</a></li>
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-white border dropdown-toggle btn-sm text-muted" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Services
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">All</a></li>
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-white border dropdown-toggle btn-sm text-muted" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Providers
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">All</a></li>
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-white border dropdown-toggle btn-sm text-muted" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Client Name
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">All</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 table-hover">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th class="ps-4 py-3 border-0">Form name</th>
                                <th class="py-3 border-0">Status</th>
                                <th class="py-3 border-0">Appointment date</th>
                                <th class="py-3 border-0">Last updated <i class='bx bx-down-arrow-alt'></i></th>
                                <th class="py-3 border-0">Client Name</th>
                                <th scope="col" class="py-3 border-0">Service</th>
                                <th scope="col" class="py-3 border-0">Provider</th>
                                <th class="pe-4 py-3 border-0 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($formRecords as $record)
                                <tr>
                                    <td class="ps-4 py-3">
                                        <span class="text-dark">{{ $record->form->name }}</span>
                                        <i class='bx bx-pencil text-muted ms-2'></i>
                                    </td>
                                    <td><span class="badge badge-soft-warning">Pending</span></td>
                                    <td class="text-muted small">-</td>
                                    <td class="text-muted small">{{ $record->updated_at->format('M j, Y') }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-initials bg-light-danger text-danger me-2 rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 25px; height: 25px; font-size: 0.7rem;">
                                                <i class='bx bx-user'></i>
                                            </div>
                                            <span class="small text-dark">{{ optional($record->client)->name ?: 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td class="text-muted small">-</td>
                                    <td class="text-muted small">-</td>
                                    <td class="pe-4 text-end">
                                        <a href="{{ route('form-records.show', $record->id) }}" class="btn btn-link text-muted p-0 me-2"><i class='bx bx-show'></i></a>
                                        <form action="{{ route('form-records.destroy', $record->id) }}" method="POST" class="d-inline"
                                            data-confirm="This submitted form record will be permanently removed."
                                            data-confirm-title="Delete form record?"
                                            data-confirm-text="Delete"
                                            data-confirm-loading="Deleting...">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-link text-muted p-0"><i class='bx bx-trash'></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">No form records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0 py-3 px-4 d-flex justify-content-end">
                {{ $formRecords->links() }}
            </div>
        </div>
    </div>
@endsection
