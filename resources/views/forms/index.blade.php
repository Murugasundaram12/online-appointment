@extends('layouts.app')

@section('title', 'Forms')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1">Forms</h1>
                <p class="text-muted mb-0">Create intake forms and manage submissions.</p>
            </div>
        </div>

        <!-- Toolbar -->
        <x-list-toolbar :paginator="$forms" searchAction="{{ route('forms.index') }}" searchPlaceholder="Search forms">
            <x-slot name="filters">
                <x-list-toolbar-filters
                    :showClear="request()->has('search') && request('search') !== '' || request()->filled('status')"
                    :clearUrl="route('forms.index', ['per_page' => request('per_page', $forms->perPage())])" />
                <div class="dropdown">
                    <button class="btn btn-light border dropdown-toggle btn-sm text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ request()->filled('status') ? (request('status') === 'active' ? 'Published' : 'Draft') : 'Status' }}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('forms.index', request()->except(['status', 'page'])) }}">All</a></li>
                        <li><a class="dropdown-item" href="{{ route('forms.index', array_merge(request()->except(['status', 'page']), ['status' => 'active'])) }}">Published</a></li>
                        <li><a class="dropdown-item" href="{{ route('forms.index', array_merge(request()->except(['status', 'page']), ['status' => 'inactive'])) }}">Draft</a></li>
                    </ul>
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('forms.create') }}" class="btn btn-primary btn-sm px-4"><i class="bx bx-plus me-1"></i>Create Form</a>
            </x-slot>
        </x-list-toolbar>

        <!-- Table -->
        <div class="bg-white rounded shadow-sm overflow-hidden mb-5">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th style="min-width: 300px;">Form name</th>
                            <th>Status</th>
                            <th>Category</th>
                            <th>Last updated <i class='bx bx-down-arrow-alt'></i></th>
                            <th>Records</th>
                            <th>Tags</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($forms as $form)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="form-icon-box">
                                            <i class='bx bx-user-voice fs-5'></i>
                                        </div>
                                        {{ $form->name }}
                                    </div>
                                </td>
                                <td>
                                    @if($form->is_active)
                                        <span class="status-dropdown badge-published">Published</span>
                                    @else
                                        <span class="status-dropdown badge-draft">Draft</span>
                                    @endif
                                </td>
                                <td class="text-muted small">-</td>
                                <td class="text-muted small">{{ $form->updated_at->format('M j, Y') }}</td>
                                <td class="text-muted small">0</td>
                                <td class="text-muted small">-</td>
                                <td class="text-end">
                                    <a href="{{ route('forms.edit', $form->id) }}" class="btn btn-link text-muted p-0 me-2"><i
                                            class='bx bx-pencil'></i></a>
                                    <form action="{{ route('forms.destroy', $form->id) }}" method="POST" class="d-inline"
                                        data-confirm="This form will be deleted if the server allows it."
                                        data-confirm-title="Delete form?"
                                        data-confirm-record="{{ $form->name }}"
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
                                <td colspan="7" class="text-center py-5 text-muted">No forms found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>
            @include('partials.pagination', ['paginator' => $forms])
        </div>
    </div>
@endsection
