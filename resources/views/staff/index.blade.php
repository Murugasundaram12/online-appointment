@extends('layouts.app')

@section('title', 'Staff List')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Staff</h1>
                <p class="text-muted mb-0">Manage team members, access levels and payroll settings.</p>
            </div>
        </div>

        <!-- Toolbar -->
        <x-list-toolbar :paginator="$staffs" searchAction="{{ route('staff.index') }}" searchPlaceholder="Search by name, email or phone">
            <x-slot name="filters">
                <x-list-toolbar-filters
                    :showClear="(request()->has('search') && request('search') !== '') || request()->filled('access_level') || request()->filled('category')"
                    :clearUrl="route('staff.index', ['per_page' => request('per_page', $staffs->perPage())])" />
                <div class="dropdown">
                    <button class="btn btn-light border dropdown-toggle btn-sm text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ request()->filled('access_level') ? ucwords(str_replace('_', ' ', request('access_level'))) : 'Access level' }}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('staff.index', array_merge(request()->except(['access_level', 'page']), ['access_level' => ''])) }}">All</a></li>
                        @foreach(['staff', 'practitioner', 'receptionist', 'admin', 'business_owner'] as $level)
                            <li><a class="dropdown-item" href="{{ route('staff.index', array_merge(request()->except(['access_level', 'page']), ['access_level' => $level])) }}">{{ ucwords(str_replace('_', ' ', $level)) }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-light border dropdown-toggle btn-sm text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ request()->filled('category') ? e(request('category')) : 'Category' }}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('staff.index', array_merge(request()->except(['category', 'page']), ['category' => ''])) }}">All</a></li>
                        @foreach($categories ?? [] as $category)
                            <li><a class="dropdown-item" href="{{ route('staff.index', array_merge(request()->except(['category', 'page']), ['category' => $category])) }}">{{ $category }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </x-slot>
            <x-slot name="actions">
                <button type="button" class="btn btn-primary btn-sm px-4" data-bs-toggle="modal" data-bs-target="#addStaffModal"><i class="bx bx-plus me-1"></i>Add Staff</button>
            </x-slot>
        </x-list-toolbar>

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
                            @forelse($staffs as $staff)
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-initials bg-light-danger text-danger me-2 rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 25px; height: 25px; font-size: 0.7rem;">
                                                <i class='bx bx-user'></i>
                                            </div>
                                            <span class="small text-dark fw-500">{{ $staff->name }}</span>
                                        </div>
                                    </td>
                                    <td class="small">{{ $staff->access_level ? ucfirst($staff->access_level) : '-' }}</td>
                                    <td class="text-muted small">{{ $staff->category ?? '-' }}</td>
                                    <td class="small text-muted">{{ $staff->email }}</td>
                                    <td class="small text-muted">
                                        {{ $staff->last_login_at ? $staff->last_login_at->format('M j, Y') : '-' }}
                                    </td>
                                    <td class="text-muted small">-</td>
                                    <td class="pe-4 text-end">
                                        <button type="button" class="btn btn-link text-muted p-0 me-2 js-edit-staff"
                                            data-bs-toggle="modal" data-bs-target="#editStaffModal"
                                            data-update-url="{{ route('staff.update', $staff->id) }}"
                                            data-name="{{ $staff->name }}" data-email="{{ $staff->email }}"
                                            data-access_level="{{ $staff->access_level }}"
                                            data-registration_number="{{ e($staff->registration_number) }}"
                                            data-designation="{{ e($staff->designation) }}"
                                            data-category="{{ $staff->category }}" data-salary="{{ $staff->salary }}"
                                            data-location_id="{{ $staff->location_id }}"
                                            data-is_active="{{ $staff->is_active ? 1 : 0 }}"
                                            data-phone="{{ $staff->phone }}" data-bio="{{ $staff->bio }}"
                                            data-color="{{ $staff->color }}">
                                            <i class='bx bx-pencil'></i>
                                        </button>
                                        <form action="{{ route('staff.destroy', $staff->id) }}" method="POST" class="d-inline"
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

            @include('partials.pagination', ['paginator' => $staffs])
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

                        <input type="hidden" name="access_level" value="staff">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-id-card'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Registration Number</label>
                                        <input type="text" class="form-control" name="registration_number" placeholder="e.g. RMT-123456">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-briefcase'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Designation</label>
                                        <input type="text" class="form-control" name="designation" placeholder="e.g. Senior Practitioner">
                                    </div>
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
                                         <div class="input-group">
                                             <input type="password" class="form-control" name="password"
                                                 placeholder="Enter password" required>
                                             <button class="btn btn-outline-secondary js-toggle-password-btn" type="button" aria-label="Toggle password visibility"><i class="bx bx-show"></i></button>
                                         </div>
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
                    <form id="edit-staff-form" action="#" method="POST">
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
                                    <div class="field-icon"><i class='bx bx-id-card'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Registration Number</label>
                                        <input type="text" class="form-control" id="edit-staff-registration-number" name="registration_number" placeholder="e.g. RMT-123456">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-briefcase'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Designation</label>
                                        <input type="text" class="form-control" id="edit-staff-designation" name="designation" placeholder="e.g. Senior Practitioner">
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
                                            <option value="staff">Staff</option>
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
                                         <div class="input-group">
                                             <input type="password" class="form-control" id="edit-staff-password"
                                                 name="password" placeholder="Leave blank to keep current">
                                             <button class="btn btn-outline-secondary js-toggle-password-btn" type="button" aria-label="Toggle password visibility"><i class="bx bx-show"></i></button>
                                         </div>
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
                    if (document.getElementById('edit-staff-registration-number')) document.getElementById('edit-staff-registration-number').value = btn.dataset.registration_number || '';
                    if (document.getElementById('edit-staff-designation')) document.getElementById('edit-staff-designation').value = btn.dataset.designation || '';
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
