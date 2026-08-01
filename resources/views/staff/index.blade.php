@extends('layouts.app')

@section('title', 'Staff List')

@push('styles')
    <style>
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

        .field-icon i {
            font-size: 1.5rem;
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
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">Staff list</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-white border btn-sm text-primary fw-500">Category manager</button>
                <button class="btn btn-primary btn-sm px-4" data-bs-toggle="modal"
                    data-bs-target="#addStaffModal">Add</button>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">

        <!-- Filters -->
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class='bx bx-search text-muted'></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" placeholder="Search">
                </div>
            </div>
            <div class="col-md-8 d-flex flex-wrap gap-2 justify-content-end align-items-center">
                <div class="dropdown">
                    <button class="btn btn-white border dropdown-toggle btn-sm text-muted" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Staff type
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">All</a></li>
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-white border dropdown-toggle btn-sm text-muted" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Access level
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">All</a></li>
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-white border dropdown-toggle btn-sm text-muted" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Category
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">All</a></li>
                    </ul>
                </div>
                <button class="btn btn-link text-muted p-0 ms-2"><i class='bx bx-hide fs-5'></i></button>
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th scope="col" class="ps-4 py-3 border-0">Staff name <i class='bx bx-up-arrow-alt'></i>
                                </th>
                                <th scope="col" class="py-3 border-0">Access level</th>
                                <th scope="col" class="py-3 border-0">Category</th>
                                <th scope="col" class="py-3 border-0">Email address</th>
                                <th scope="col" class="py-3 border-0">Last login</th>
                                <th scope="col" class="py-3 border-0">Payroll</th>
                                <th scope="col" class="pe-4 py-3 border-0 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @forelse($staff as $member)
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-initials bg-light-danger text-danger me-2 rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 25px; height: 25px; font-size: 0.7rem;">
                                                <i class='bx bx-user'></i>
                                            </div>
                                            <span class="small text-dark fw-500">{{ $member->name }}</span>
                                        </div>
                                    </td>
                                    <td class="small">{{ $member->access_level ? ucfirst($member->access_level) : '-' }}</td>
                                    <td class="text-muted small">{{ $member->category ?? '-' }}</td>
                                    <td class="small text-muted">{{ $member->email }}</td>
                                    <td class="small text-muted">
                                        {{ $member->last_login_at ? $member->last_login_at->format('M j, Y') : '-' }}
                                    </td>
                                    <td class="text-muted small">-</td>
                                    <td class="pe-4 text-end">
                                        <button type="button" class="btn btn-link text-muted p-0 me-2 js-edit-staff"
                                            data-bs-toggle="modal" data-bs-target="#editStaffModal"
                                            data-update-url="{{ route('staff.update', $member->id) }}"
                                            data-name="{{ $member->name }}" data-email="{{ $member->email }}"
                                            data-access_level="{{ $member->access_level }}"
                                            data-category="{{ $member->category }}" data-salary="{{ $member->salary }}"
                                            data-location_id="{{ $member->location_id }}"
                                            data-is_active="{{ $member->is_active ? 1 : 0 }}"
                                            data-phone="{{ $member->phone }}" data-bio="{{ $member->bio }}"
                                            data-color="{{ $member->color }}">
                                            <i class='bx bx-pencil'></i>
                                        </button>
                                        <form action="{{ route('staff.destroy', $member->id) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link text-muted p-0"><i
                                                    class='bx bx-trash'></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No staff members found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Pagination Footer -->
            <div
                class="card-footer bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center small text-muted">
                <div class="d-flex align-items-center gap-2">
                    <span>Rows per page</span>
                    <select class="form-select form-select-sm border-0 bg-light" style="width: auto;">
                        <option>10</option>
                        <option>25</option>
                        <option>50</option>
                    </select>
                </div>
                <div class="d-flex align-items-center gap-3">
                    {{ $staff->links() }}
                </div>
            </div>
        </div>

    </div>
    <!-- Add Staff Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStaffModalLabel">Add Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="add-staff-form" action="{{ route('staff.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-user'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Staff name <span class="required-mark">*</span></label>
                                        <input type="text" class="form-control" name="name" placeholder="Enter staff name"
                                            required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-shield-quarter'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Access level</label>
                                        <select class="form-select" name="access_level">
                                            <option selected disabled>Select access level</option>
                                            <option value="business_owner">Business owner</option>
                                            <option value="receptionist">Receptionist</option>
                                            <option value="practitioner">Practitioner</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-category'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Category</label>
                                        <select class="form-select" name="category">
                                            <option value="" selected>All</option>
                                            <option value="Massage Therapy">Massage Therapy</option>
                                            <option value="Physiotherapy">Physiotherapy</option>
                                            <option value="Chiropractic">Chiropractic</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-map'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Location</label>
                                        <select class="form-select" name="location_id">
                                            <option value="">No location assigned</option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-envelope'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Email address <span class="required-mark">*</span></label>
                                        <input type="email" class="form-control" name="email"
                                            placeholder="Enter email address" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-lock-alt'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Password <span class="required-mark">*</span></label>
                                        <input type="password" class="form-control" name="password"
                                            placeholder="Enter password" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group mb-0">
                                    <div class="field-icon"><i class='bx bx-money'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Payroll settings</label>
                                        <input type="number" step="0.01" min="0" name="salary" class="form-control"
                                            placeholder="Commission / Hourly rate details">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="is_active" value="1">
                        <input type="hidden" name="phone" value="">
                        <input type="hidden" name="bio" value="">
                        <input type="hidden" name="color" value="">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="add-staff-form" class="btn btn-submit">Save Staff</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Staff Modal -->
    <div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStaffModalLabel">Edit Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="edit-staff-form" action="route('staff.update', $staff->id)" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-user'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Staff name <span class="required-mark">*</span></label>
                                        <input type="text" class="form-control" id="edit-staff-name" name="name"
                                            placeholder="Enter staff name" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-shield-quarter'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Access level</label>
                                        <select class="form-select" id="edit-staff-access-level" name="access_level">
                                            <option disabled>Select access level</option>
                                            <option value="business_owner">Business owner</option>
                                            <option value="receptionist">Receptionist</option>
                                            <option value="practitioner">Practitioner</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-category'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Category</label>
                                        <select class="form-select" id="edit-staff-category" name="category">
                                            <option value="">All</option>
                                            <option value="Massage Therapy">Massage Therapy</option>
                                            <option value="Physiotherapy">Physiotherapy</option>
                                            <option value="Chiropractic">Chiropractic</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-map'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Location</label>
                                        <select class="form-select" id="edit-staff-location" name="location_id">
                                            <option value="">No location assigned</option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-envelope'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Email address <span class="required-mark">*</span></label>
                                        <input type="email" class="form-control" id="edit-staff-email" name="email"
                                            placeholder="Enter email address" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-lock-alt'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Password</label>
                                        <input type="password" class="form-control" id="edit-staff-password"
                                            name="password" placeholder="Leave blank to keep current">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group mb-0">
                                    <div class="field-icon"><i class='bx bx-money'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Payroll settings</label>
                                        <input type="number" step="0.01" min="0" name="salary"
                                            id="edit-staff-salary" class="form-control"
                                            placeholder="Commission / Hourly rate details">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="is_active" id="edit-staff-is-active" value="1">
                        <input type="hidden" name="phone" id="edit-staff-phone" value="">
                        <input type="hidden" name="bio" id="edit-staff-bio" value="">
                        <input type="hidden" name="color" id="edit-staff-color" value="">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="edit-staff-form" class="btn btn-submit">Update Staff</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editForm = document.getElementById('edit-staff-form');
            const buttons = document.querySelectorAll('.js-edit-staff');
            if (!editForm || buttons.length === 0) return;

            buttons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    editForm.action = btn.dataset.updateUrl || '#';
                    document.getElementById('edit-staff-name').value = btn.dataset.name || '';
                    document.getElementById('edit-staff-email').value = btn.dataset.email || '';
                    document.getElementById('edit-staff-access-level').value = btn.dataset.access_level || '';
                    document.getElementById('edit-staff-category').value = btn.dataset.category || '';
                    document.getElementById('edit-staff-location').value = btn.dataset.location_id || '';
                    document.getElementById('edit-staff-salary').value = btn.dataset.salary || '';
                    document.getElementById('edit-staff-password').value = '';
                    document.getElementById('edit-staff-is-active').value = btn.dataset.is_active || '0';
                    document.getElementById('edit-staff-phone').value = btn.dataset.phone || '';
                    document.getElementById('edit-staff-bio').value = btn.dataset.bio || '';
                    document.getElementById('edit-staff-color').value = btn.dataset.color || '';
                });
            });
        });
    </script>
@endpush
