@extends('layouts.app')

@section('title', 'Clients List')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Clients</h1>
                <p class="text-muted mb-0">Manage your client directory and contact details.</p>
            </div>
        </div>

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

        <!-- Toolbar -->
        <x-list-toolbar :paginator="$clients" searchAction="{{ route('clients.index') }}" searchPlaceholder="Search by name, email, phone or city">
            <x-slot name="filters">
                <x-list-toolbar-filters
                    :showClear="request()->has('search') && request('search') !== ''"
                    :clearUrl="route('clients.index', ['per_page' => request('per_page', $clients->perPage())])"
                    clearLabel="Clear search" />
            </x-slot>
            <x-slot name="actions">
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addClientModal"><i class="bx bx-plus me-1"></i>Add New Client</button>
            </x-slot>
        </x-list-toolbar>

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
                                            <a href="{{ route('clients.show', $client->id) }}" class="fw-semibold text-dark text-decoration-none hover-primary">
                                                {{ $client->name }}
                                            </a>
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
                                            data-first-name="{{ e($client->first_name) }}"
                                            data-last-name="{{ e($client->last_name) }}"
                                            data-email="{{ e($client->email) }}"
                                            data-phone="{{ e($client->phone) }}"
                                            data-alternate-phone="{{ e($client->alternate_phone) }}"
                                            data-gender="{{ e($client->gender) }}"
                                            data-dob="{{ $client->dob ? $client->dob->format('Y-m-d') : '' }}"
                                            data-client-since="{{ $client->client_since ? $client->client_since->format('Y-m-d') : '' }}"
                                            data-address-line1="{{ e($client->address_line1) }}"
                                            data-address-line2="{{ e($client->address_line2) }}"
                                            data-city="{{ e($client->city) }}"
                                            data-state="{{ e($client->state) }}"
                                            data-country="{{ e($client->country) }}"
                                            data-postal-code="{{ e($client->postal_code) }}"
                                            data-emergency-contact="{{ e($client->emergency_contact) }}"
                                            data-emergency-phone="{{ e($client->emergency_phone) }}"
                                            data-notes="{{ e($client->notes) }}"
                                            data-is-vip="{{ $client->is_vip ? '1' : '0' }}">
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
                        @include('clients.partials.form-fields', ['idPrefix' => 'add-client-', 'client' => null])
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
                        @include('clients.partials.form-fields', ['idPrefix' => 'edit-client-'])
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
                    const setVal = (id, val) => {
                        const el = document.getElementById(id);
                        if (el) {
                            el.value = val || '';
                            el.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    };
                    setVal('edit-client-first-name', btn.dataset.firstName);
                    setVal('edit-client-last-name', btn.dataset.lastName);
                    setVal('edit-client-email', btn.dataset.email);
                    setVal('edit-client-phone', btn.dataset.phone);
                    setVal('edit-client-alternate-phone', btn.dataset.alternatePhone);
                    setVal('edit-client-gender', btn.dataset.gender);
                    setVal('edit-client-dob', btn.dataset.dob);
                    setVal('edit-client-client-since', btn.dataset.clientSince);
                    setVal('edit-client-address-line1', btn.dataset.addressLine1);
                    setVal('edit-client-address-line2', btn.dataset.addressLine2);
                    setVal('edit-client-city', btn.dataset.city);
                    setVal('edit-client-state', btn.dataset.state);
                    setVal('edit-client-country', btn.dataset.country);
                    setVal('edit-client-postal-code', btn.dataset.postalCode);
                    setVal('edit-client-emergency-contact', btn.dataset.emergencyContact);
                    setVal('edit-client-emergency-phone', btn.dataset.emergencyPhone);
                    setVal('edit-client-notes', btn.dataset.notes);

                    const vipEl = document.getElementById('edit-client-is-vip');
                    if (vipEl) vipEl.checked = (btn.dataset.isVip === '1');
                });
            });
        });
    </script>
@endpush
