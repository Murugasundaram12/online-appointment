@extends('layouts.app')

@section('title', 'Form Records')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Form Records</h1>
                <p class="text-muted mb-0">Review submissions collected from your forms.</p>
            </div>
        </div>

        <!-- Toolbar -->
        <x-list-toolbar :paginator="$formRecords" searchAction="{{ route('form-records.index') }}" searchPlaceholder="Search records">
            <x-slot name="filters">
                <x-list-toolbar-filters
                    :showClear="request()->has('search') && request('search') !== ''"
                    :clearUrl="route('form-records.index', ['per_page' => request('per_page', $formRecords->perPage())])"
                    clearLabel="Clear search" />
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('form-records.create') }}" class="btn btn-primary btn-sm px-4"><i class="bx bx-plus me-1"></i>Add Record</a>
            </x-slot>
        </x-list-toolbar>

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
            @include('partials.pagination', ['paginator' => $formRecords])
        </div>
    </div>
@endsection
