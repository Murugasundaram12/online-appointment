@extends('layouts.app')

@section('title', 'Clients List')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <div class="d-flex align-items-center">
                <div class="nav-icon-box me-3">
                    <i class='bx bx-cog'></i>
                    <span class="plus-badge">+</span>
                </div>
                <div class="nav-icon-box">
                    <i class='bx bx-camera-plus'></i>
                </div>
            </div>
            <h2 class="fs-4 m-0 fw-bold">Clients list</h2>

            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm text-white" data-bs-toggle="modal"
                    data-bs-target="#addClientModal">Add new client</button>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">

        <!-- Stats Bar -->
        <div class="bg-white border rounded p-3 mb-4 d-flex align-items-center gap-4 text-muted small shadow-sm">
            <div>
                Total clients <span class="fs-5 text-dark fw-bold ms-2">{{ $clients->total() }}</span>
            </div>
            <div class="vr"></div>
            <div>
                New clients in past 30 days <span class="fs-5 text-dark fw-bold ms-2">{{ $newClientsCount ?? 0 }}</span>
            </div>
        </div>

        <!-- Filters -->
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <form method="GET" action="{{ route('clients.index') }}" id="clientsSearchForm">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class='bx bx-search text-muted'></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0" placeholder="Search by name, email, phone or city">
                    </div>
                </form>
            </div>
            <div class="col-md-8 d-flex justify-content-end gap-2">
                @if(request()->has('search') && request('search') !== '')
                    <a href="{{ route('clients.index', ['per_page' => request('per_page', 10)]) }}" class="btn btn-white border text-muted">Clear search</a>
                @endif
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th scope="col" class="ps-4 py-3 border-0">Client name <i class='bx bx-up-arrow-alt'></i>
                                </th>
                                <th scope="col" class="py-3 border-0">Email address</th>
                                <th scope="col" class="py-3 border-0">Phone number</th>
                                <th scope="col" class="py-3 border-0">Client since</th>
                                <th scope="col" class="py-3 border-0">Tags</th>
                                <th scope="col" class="pe-4 py-3 border-0 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @forelse($clients as $client)
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-initials bg-light-danger text-danger me-3 rounded d-flex align-items-center justify-content-center"
                                                style="width: 32px; height: 32px;">
                                                {{ substr($client->name, 0, 2) }}
                                            </div>
                                            <span class="fw-medium text-dark">{{ $client->name }}</span>
                                        </div>
                                    </td>
                                    <td class="text-muted small">{{ $client->email ?? '-' }}</td>
                                    <td class="text-muted small">{{ $client->phone ?? '-' }}</td>
                                    <td class="text-muted small">
                                        {{ $client->client_since ? \Carbon\Carbon::parse($client->client_since)->format('M d, Y') : ($client->created_at ? $client->created_at->format('M d, Y') : '-') }}
                                    </td>
                                    <td class="text-muted small">{{ $client->is_vip ? 'VIP' : '-' }}</td>
                                    <td class="pe-4 text-end">
                                        <button type="button" class="btn btn-link text-muted p-0 me-2 js-edit-client"
                                            data-bs-toggle="modal" data-bs-target="#editClientModal"
                                            data-update-url="{{ route('clients.update', $client->id) }}"
                                            data-name="{{ e($client->name) }}" data-email="{{ e($client->email) }}"
                                            data-phone="{{ e($client->phone) }}"
                                            data-client-since="{{ $client->client_since ? $client->client_since->format('Y-m-d') : '' }}"
                                            data-tags="{{ $client->is_vip ? 'VIP' : '' }}"
                                            data-notes="{{ e($client->notes) }}">
                                            <i class='bx bx-pencil'></i>
                                        </button>
                                        <form action="{{ route('clients.destroy', $client->id) }}" method="POST"
                                            class="d-inline"
                                            data-confirm="This action cannot be undone. Clients with appointments, invoices, or form records cannot be deleted."
                                            data-confirm-title="Delete client?"
                                            data-confirm-record="{{ $client->name }}"
                                            data-confirm-text="Delete"
                                            data-confirm-loading="Deleting...">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link text-muted p-0"><i
                                                    class='bx bx-trash'></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No clients found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Pagination -->
            @include('partials.pagination', ['paginator' => $clients])
        </div>
    </div>
    <!-- Add Client Modal -->
    <div class="modal fade app-modal" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClientModalLabel">Add new client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addClientForm" method="POST" action="{{ route('clients.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-user'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Client name <span class="required-mark">*</span></label>
                                        <input type="text" class="form-control" name="name" placeholder="Enter full name"
                                            required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-envelope'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Email address</label>
                                        <input type="email" class="form-control" name="email"
                                            placeholder="Enter email address">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-phone'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Phone number</label>
                                        <input type="tel" class="form-control" name="phone"
                                            placeholder="Enter phone number">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-calendar'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Client since</label>
                                        <input type="date" class="form-control" name="client_since">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-hash'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Tags</label>
                                        <input type="text" class="form-control" name="tags"
                                            placeholder="Add tags (e.g. VIP, New)">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group mb-0">
                                    <div class="field-icon"><i class='bx bx-note'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" rows="3" name="notes"
                                            placeholder="Add any private notes about this client"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="addClientForm" class="btn btn-submit">Add client</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Client Modal -->
    <div class="modal fade app-modal" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editClientModalLabel">Edit client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editClientForm" method="POST" action="#">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-user'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Client name <span class="required-mark">*</span></label>
                                        <input type="text" class="form-control" id="edit-client-name" name="name"
                                            placeholder="Enter full name" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-envelope'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Email address</label>
                                        <input type="email" class="form-control" id="edit-client-email" name="email"
                                            placeholder="Enter email address">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-phone'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Phone number</label>
                                        <input type="tel" class="form-control" id="edit-client-phone" name="phone"
                                            placeholder="Enter phone number">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-calendar'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Client since</label>
                                        <input type="date" class="form-control" id="edit-client-since"
                                            name="client_since">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-hash'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Tags</label>
                                        <input type="text" class="form-control" id="edit-client-tags" name="tags"
                                            placeholder="Add tags (e.g. VIP, New)">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group mb-0">
                                    <div class="field-icon"><i class='bx bx-note'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" rows="3" id="edit-client-notes" name="notes"
                                            placeholder="Add any private notes about this client"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="editClientForm" class="btn btn-submit">Update client</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Reset Add Client form when modal is shown to prevent values appending
            const addClientModal = document.getElementById('addClientModal');
            if (addClientModal) {
                addClientModal.addEventListener('show.bs.modal', () => {
                    const addForm = document.getElementById('addClientForm');
                    if (addForm) {
                        addForm.reset();
                    }
                });
            }

            const editForm = document.getElementById('editClientForm');
            const buttons = document.querySelectorAll('.js-edit-client');
            if (!editForm) return;

            buttons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    editForm.action = btn.dataset.updateUrl || '#';
                    document.getElementById('edit-client-name').value = btn.dataset.name || '';
                    document.getElementById('edit-client-email').value = btn.dataset.email || '';
                    document.getElementById('edit-client-phone').value = btn.dataset.phone || '';
                    document.getElementById('edit-client-since').value = btn.dataset.clientSince || '';
                    document.getElementById('edit-client-tags').value = btn.dataset.tags || '';
                    document.getElementById('edit-client-notes').value = btn.dataset.notes || '';
                });
            });
        });
    </script>
@endpush
