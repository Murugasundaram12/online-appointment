@extends('layouts.app')

@section('title', 'Add Schedule')

@push('styles')
    <style>
        .schedule-form-card {
            border: 1px solid #eef0f7;
        }

        .schedule-form-card .form-label {
            font-weight: 600;
        }

        .schedule-section-title {
            font-weight: 700;
            color: #3f4254;
        }

        .schedule-help {
            color: #7e8299;
            font-size: 0.85rem;
        }

        .schedule-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: end;
        }

        .schedule-toolbar .btn {
            white-space: nowrap;
        }
    </style>
@endpush

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">Add Schedule for {{ $staff->name }}</h2>
            <a href="{{ route('schedule.index', ['staff_id' => $staff->id]) }}"
                class="btn btn-white border btn-sm text-muted">Back to Schedule</a>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="card shadow-sm rounded schedule-form-card">
            <div class="card-body p-4">
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route('schedule.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="staff_id" value="{{ $staff->id }}">

                    <h5 class="mb-3 schedule-section-title">Default Times</h5>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label class="form-label small text-muted">Staff Name</label>
                            <input type="text" class="form-control" value="{{ $staff->name }}" readonly>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small text-muted">Category</label>
                            <input type="text" class="form-control" value="{{ $staff->category ?? '' }}" readonly>
                        </div>
                        {{-- <div class="col-6 col-md-2">
                            <label class="form-label small text-muted">Default Start</label>
                            <input type="time" class="form-control" id="default-start-time" value="09:00">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small text-muted">Default End</label>
                            <input type="time" class="form-control" id="default-end-time" value="17:00">
                        </div>
                        <div class="col-12 schedule-toolbar">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="apply-default-times">
                                Apply default times
                            </button>
                        </div> --}}
                    
                        <div class="col-12 col-md-4">
                            <label class="form-label small text-muted">Date <span class="required-mark">*</span></label>
                            <input type="date" class="form-control" name="working_date" id="working-date" required>
                            <div class="form-text">Creates/updates schedule for this date.</div>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label small text-muted">Start Time <span class="required-mark">*</span></label>
                            <input type="time" class="form-control" name="start_time" id="single-start-time" value="09:00" required>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label small text-muted">End Time <span class="required-mark">*</span></label>
                            <input type="time" class="form-control" name="end_time" id="single-end-time" value="17:00" required>
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary px-4">Save Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btnApply = document.getElementById('apply-default-times');
            const defaultStart = document.getElementById('default-start-time');
            const defaultEnd = document.getElementById('default-end-time');
            const singleStart = document.getElementById('single-start-time');
            const singleEnd = document.getElementById('single-end-time');
            if (!btnApply || !defaultStart || !defaultEnd) return;

            btnApply.addEventListener('click', function () {
                const startVal = defaultStart.value;
                const endVal = defaultEnd.value;
                if (singleStart) singleStart.value = startVal;
                if (singleEnd) singleEnd.value = endVal;
            });
        });
    </script>
@endpush
