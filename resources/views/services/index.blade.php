@extends('layouts.app')

@section('title', 'Services List')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-transparent py-4 px-4">
        <div class="d-flex align-items-center justify-content-between w-100">
            <h2 class="fs-4 m-0 fw-bold">Services list</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-custom px-4">Category manager</button>
                <button class="btn btn-primary px-4 fw-500" data-bs-toggle="modal"
                    data-bs-target="#addServiceModal">Add</button>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">

        <!-- Filters -->
        <div class="d-flex flex-wrap gap-3 align-items-center mb-4">
            <div class="search-container flex-grow-1" style="max-width: 400px;">
                <i class='bx bx-search'></i>
                <form method="GET" action="{{ route('services.index') }}">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control search-input" placeholder="Search">
                </form>
            </div>
            <div class="dropdown">
                <button class="btn filter-select dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Category
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('services.index', request()->except(['category_id', 'page'])) }}">All</a></li>
                    @foreach($categories as $category)
                        <li><a class="dropdown-item" href="{{ route('services.index', array_merge(request()->except(['category_id', 'page']), ['category_id' => $category->id])) }}">{{ $category->name }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="dropdown">
                <button class="btn filter-select dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Service type
                </button>
            </div>
            <div class="dropdown">
                <button class="btn filter-select dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Price
                </button>
            </div>
            <div class="dropdown">
                <button class="btn filter-select dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Duration
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded shadow-sm overflow-hidden mb-5">
            <div class="d-flex justify-content-end p-3 bg-white border-bottom">
                <i class='bx bx-hide text-muted fs-5'></i>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th scope="col" class="ps-4 py-3 border-0">Service name <i class='bx bx-up-arrow-alt'></i>
                            </th>
                            <th scope="col" class="py-3 border-0">Category</th>
                            <th scope="col" class="py-3 border-0">Service type</th>
                            <th scope="col" class="py-3 border-0 text-end">Price</th>
                            <th scope="col" class="py-3 border-0">Duration</th>
                            <th scope="col" class="pe-4 py-3 border-0 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($services as $service)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="service-icon-box me-3"
                                            style="width: 32px; height: 32px; background-color: #f8f9fa; border: 1px solid #eef0f7; display: flex; align-items: center; justify-content: center; border-radius: 6px;">
                                            <i class='bx bx-edit-alt' style="color: {{ $service->color ?? '#7e8299' }};"></i>
                                        </div>
                                        <span class="fw-medium text-dark">{{ $service->name }}</span>
                                    </div>
                                </td>
                                <td class="text-muted small">{{ optional($service->category)->name ?? '-' }}</td>
                                <td class="text-muted small">{{ $service->type ? ucfirst(str_replace('_', ' ', $service->type)) : '-' }}</td>
                                <td class="text-muted small text-end">{{ is_numeric($service->price) ? '$' . number_format((float) $service->price, 2) : '-' }}</td>
                                <td class="text-muted small">{{ $service->duration_minutes ? $service->duration_minutes . ' mins' : '-' }}</td>
                                <td class="pe-4 text-end">
                                    <button type="button" class="btn btn-link text-muted p-0 me-2 js-edit-service"
                                        data-bs-toggle="modal" data-bs-target="#editServiceModal"
                                        data-update-url="{{ route('services.update', $service->id) }}"
                                        data-name="{{ $service->name }}"
                                        data-service_category_id="{{ $service->service_category_id }}"
                                        data-type="{{ $service->type }}"
                                        data-price="{{ $service->price }}"
                                        data-duration_minutes="{{ $service->duration_minutes }}"
                                        data-description="{{ $service->description }}"
                                        data-buffer_minutes="{{ $service->buffer_minutes }}"
                                        data-is_active="{{ $service->is_active ? 1 : 0 }}"
                                        data-color="{{ $service->color }}">
                                        <i class='bx bx-pencil'></i>
                                    </button>
                                    <form action="{{ route('services.destroy', $service->id) }}" method="POST" class="d-inline"
                                        data-confirm="This action cannot be undone. Services connected to appointments may be protected by the server."
                                        data-confirm-title="Delete service?"
                                        data-confirm-record="{{ $service->name }}"
                                        data-confirm-text="Delete"
                                        data-confirm-loading="Deleting...">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link text-muted p-0"><i
                                                class='bx bx-dots-vertical-rounded'></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No services found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination -->
        @include('partials.pagination', ['paginator' => $services])
    </div>
    <!-- Add Service Modal -->
    <div class="modal fade app-modal" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addServiceModalLabel">Add Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="add-service-form" action="{{ route('services.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-layer-plus'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Service name <span class="required-mark">*</span></label>
                                        <input type="text" class="form-control" name="name"
                                            placeholder="e.g. Massagetherapy - 60 Mins">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-category'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Category</label>
                                        <select class="form-select" name="service_category_id">
                                            <option value="" selected>Select category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-list-check'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Service type</label>
                                        <select class="form-select" name="type">
                                            <option value="in_person" selected>In-person</option>
                                            <option value="online">Online</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-dollar-circle'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Price (CA$) <span class="required-mark">*</span></label>
                                        <input type="number" step="0.01" min="0" class="form-control" name="price"
                                            placeholder="0.00" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-time-five'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Duration <span class="required-mark">*</span></label>
                                        <select class="form-select" name="duration_minutes" required>
                                            <option value="30">30 mins</option>
                                            <option value="45">45 mins</option>
                                            <option value="60" selected>1 hr</option>
                                            <option value="90">1 hr 30 mins</option>
                                            <option value="120">2 hrs</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group mb-0">
                                    <div class="field-icon"><i class='bx bx-detail'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="3"
                                            placeholder="Describe the service..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="buffer_minutes" value="0">
                        <input type="hidden" name="is_active" value="1">
                        <input type="hidden" name="color" value="">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="add-service-form" class="btn btn-submit">Save Service</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Service Modal -->
    <div class="modal fade app-modal" id="editServiceModal" tabindex="-1" aria-labelledby="editServiceModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editServiceModalLabel">Edit Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="edit-service-form" action="#" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-layer-plus'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Service name <span class="required-mark">*</span></label>
                                        <input type="text" class="form-control" id="edit-service-name" name="name"
                                            placeholder="e.g. Massagetherapy - 60 Mins">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-category'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Category</label>
                                        <select class="form-select" id="edit-service-category" name="service_category_id">
                                            <option value="" selected>Select category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-list-check'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Service type</label>
                                        <select class="form-select" id="edit-service-type" name="type">
                                            <option value="in_person">In-person</option>
                                            <option value="online">Online</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-dollar-circle'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Price (CA$) <span class="required-mark">*</span></label>
                                        <input type="number" step="0.01" min="0" class="form-control"
                                            id="edit-service-price" name="price" placeholder="0.00" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-group">
                                    <div class="field-icon"><i class='bx bx-time-five'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Duration <span class="required-mark">*</span></label>
                                        <select class="form-select" id="edit-service-duration" name="duration_minutes"
                                            required>
                                            <option value="30">30 mins</option>
                                            <option value="45">45 mins</option>
                                            <option value="60">1 hr</option>
                                            <option value="90">1 hr 30 mins</option>
                                            <option value="120">2 hrs</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-group mb-0">
                                    <div class="field-icon"><i class='bx bx-detail'></i></div>
                                    <div class="field-content">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" id="edit-service-description" name="description" rows="3"
                                            placeholder="Describe the service..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="buffer_minutes" id="edit-service-buffer" value="0">
                        <input type="hidden" name="is_active" id="edit-service-is-active" value="1">
                        <input type="hidden" name="color" id="edit-service-color" value="">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="edit-service-form" class="btn btn-submit">Update Service</button>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const editForm = document.getElementById('edit-service-form');
        const buttons = document.querySelectorAll('.js-edit-service');
        if (!editForm || buttons.length === 0) return;

        buttons.forEach((btn) => {
            btn.addEventListener('click', () => {
                editForm.action = btn.dataset.updateUrl || '#';
                document.getElementById('edit-service-name').value = btn.dataset.name || '';
                document.getElementById('edit-service-category').value = btn.dataset.service_category_id || '';
                document.getElementById('edit-service-type').value = btn.dataset.type || 'in_person';
                document.getElementById('edit-service-price').value = btn.dataset.price || '';
                document.getElementById('edit-service-duration').value = btn.dataset.duration_minutes || '60';
                document.getElementById('edit-service-description').value = btn.dataset.description || '';
                document.getElementById('edit-service-buffer').value = btn.dataset.buffer_minutes || '0';
                document.getElementById('edit-service-is-active').value = btn.dataset.is_active || '0';
                document.getElementById('edit-service-color').value = btn.dataset.color || '';
            });
        });
    });
</script>
@endpush
