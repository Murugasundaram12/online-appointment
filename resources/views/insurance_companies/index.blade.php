@extends('layouts.app')

@section('title', 'Insurance Companies')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Insurance Companies</h1>
                <p class="text-muted mb-0">Manage insurance provider companies and organizations.</p>
            </div>
            <a href="{{ route('insurance-companies.create') }}" class="btn btn-primary btn-sm px-4">
                <i class="bx bx-plus me-1"></i>Add Insurance Company
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th scope="col" class="ps-4 py-3 border-0">Company Name</th>
                                <th scope="col" class="py-3 border-0">Assigned Client Policies</th>
                                <th scope="col" class="pe-4 py-3 border-0 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @forelse($insuranceCompanies as $company)
                                <tr>
                                    <td class="ps-4 py-3 fw-semibold text-dark">{{ $company->name }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $company->insurance_informations_count }} records</span></td>
                                    <td class="pe-4 text-end">
                                        <a href="{{ route('insurance-companies.edit', $company->id) }}" class="btn btn-link text-muted p-0 me-2" title="Edit">
                                            <i class="bx bx-pencil"></i>
                                        </a>
                                        <form action="{{ route('insurance-companies.destroy', $company->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this insurance company?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link text-muted p-0" title="Delete">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No insurance companies found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @include('partials.pagination', ['paginator' => $insuranceCompanies])
        </div>
    </div>
@endsection
