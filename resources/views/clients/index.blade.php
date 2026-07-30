@extends('layouts.app')

@section('title', 'Clients List')

@push('styles')
    <style>
        /* Standardized Header/Sidebar Styles */
        .dot-active {
            width: 8px;
            height: 8px;
            background-color: white;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
        }

        .nav-icon-box {
            width: 40px;
            height: 40px;
            background-color: #f3f6f9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7e8299;
            font-size: 1.25rem;
            position: relative;
        }

        .plus-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #3699ff;
            color: white;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Modal Premium Styles */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            border-bottom: 1px solid #ebedf3;
            padding: 1.5rem 2rem;
        }

        .modal-title {
            font-weight: 700;
            color: #181c32;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            border-top: 1px solid #ebedf3;
            padding: 1.5rem 2rem;
            display: flex;
            gap: 1rem;
        }

        .field-group {
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
        }

        .field-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #a1a5b7;
            margin-top: 0.5rem;
        }

        .field-content {
            flex-grow: 1;
        }

        .form-label {
            font-weight: 600;
            color: #3f4254;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        .required-label::after {
            content: '*';
            color: #f64e60;
            margin-left: 0.25rem;
        }

        .modal-body .form-control,
        .modal-body .form-select {
            background-color: #f9f9f9;
            border: 1px solid #e1e3ea;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            color: #3f4254;
        }

        .modal-body .form-control:focus,
        .modal-body .form-select:focus {
            background-color: white;
            border-color: #3699ff;
            box-shadow: none;
        }

        .btn-cancel {
            background-color: #f3f6f9;
            color: #7e8299;
            border: none;
            font-weight: 600;
            padding: 0.75rem 1.75rem;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            background-color: #eef2f7;
            color: #3f4254;
        }

        .btn-submit {
            background-color: #3699ff;
            color: white;
            border: none;
            font-weight: 600;
            padding: 0.75rem 1.75rem;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-submit:hover {
            background-color: #187de4;
        }
    </style>
@endpush

@section('content')
    {{-- <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <div class="d-flex align-items-center">
                <div class="nav-icon-box me-3">
                    <i class='bx bx-cog'></i>
                    <span class="plus-badge">+</span>
                </div>
                <div class="nav-icon-box">
                    <i class='bx bx-camera-plus'></i>
                </div>
                <h2 class="fs-4 m-0 fw-bold">Clients list</h2>
            </div>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-white border dropdown-toggle btn-sm text-primary" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Client actions
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Import clients</a></li>
                        <li><a class="dropdown-item" href="#">Export clients</a></li>
                    </ul>
                </div>
                <button class="btn btn-primary btn-sm text-white" data-bs-toggle="modal"
                    data-bs-target="#addClientModal">Add new client</button>
            </div>
        </div>
    </nav> --}}
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
                <div class="dropdown">
                    <button class="btn btn-white border dropdown-toggle btn-sm text-primary" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Client actions
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Import clients</a></li>
                        <li><a class="dropdown-item" href="#">Export clients</a></li>
                    </ul>
                </div>
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
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class='bx bx-search text-muted'></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" placeholder="Search">
                </div>
            </div>
            <div class="col-md-8 d-flex justify-content-end gap-2">
                <div class="dropdown">
                    <button class="btn btn-white border dropdown-toggle text-muted" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        Client since
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">All time</a></li>
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-white border dropdown-toggle text-muted" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        Tags
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">VIP</a></li>
                    </ul>
                </div>
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
                                            data-name="{{ $client->name }}" data-email="{{ $client->email }}"
                                            data-phone="{{ $client->phone }}"
                                            data-client_since="{{ $client->client_since ? $client->client_since->format('Y-m-d') : '' }}"
                                            data-tags="{{ $client->is_vip ? 'VIP' : '' }}"
                                            data-notes="{{ $client->notes }}">
                                            <i class='bx bx-pencil'></i>
                                        </button>
                                        <form action="{{ route('clients.destroy', $client->id) }}" method="POST"
                                            class="d-inline" onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link text-muted p-0"><i
                                                    class='bx bx-dots-vertical-rounded'></i></button>
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
            <div class="card-footer bg-white border-0 py-3 px-4 d-flex justify-content-end">
                {{ $clients->links() }}
            </div>
        </div>
    </div>
    <!-- Add Client Modal -->
    <div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
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
                                        <label class="form-label required-label">Client name</label>
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
    <div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editClientModalLabel">Edit client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editClientForm" method="POST" action="{{ route('clients.store') }}">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-user'></i></div>
                                    <div class="field-content">
                                        <label class="form-label required-label">Client name</label>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editForm = document.getElementById('editClientForm');
            const buttons = document.querySelectorAll('.js-edit-client');
            if (!editForm || buttons.length === 0) return;

            buttons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    editForm.action = btn.dataset.updateUrl || '#';
                    document.getElementById('edit-client-name').value = btn.dataset.name || '';
                    document.getElementById('edit-client-email').value = btn.dataset.email || '';
                    document.getElementById('edit-client-phone').value = btn.dataset.phone || '';
                    document.getElementById('edit-client-since').value = btn.dataset.client_since || '';
                    document.getElementById('edit-client-tags').value = btn.dataset.tags || '';
                    document.getElementById('edit-client-notes').value = btn.dataset.notes || '';
                });
            });
        });
    </script>
@endpush
