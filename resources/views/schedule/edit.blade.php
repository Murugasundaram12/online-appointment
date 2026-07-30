@extends('layouts.app')

@section('title', 'Edit Schedule')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <div class="d-flex align-items-center w-100 justify-content-between">
            <h2 class="fs-4 m-0 fw-bold">Edit Schedule for {{ $staff->name }}</h2>
            <a href="{{ route('schedule.index', ['staff_id' => $staff->id]) }}"
                class="btn btn-white border btn-sm text-muted">Back to Schedule</a>
        </div>
    </nav>

    <div class="container-fluid px-4 pt-4">
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body p-4">
                <form action="{{ route('schedule.update', $staff->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <h5 class="mb-4">Regular Working Hours</h5>

                    <div class="table-responsive">
                        <table class="table table-borderless align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 150px;">Day</th>
                                    <th style="width: 100px;">Working?</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                    @php
                                        $schedule = $schedules->firstWhere('day_of_week', $day);
                                    @endphp
                                    <tr>
                                        <td class="fw-medium text-capitalize">{{ $day }}</td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"
                                                    name="days[{{ $day }}][is_working]" value="1" {{ $schedule && $schedule->is_working ? 'checked' : '' }}>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="time" class="form-control" name="days[{{ $day }}][start_time]"
                                                value="{{ $schedule ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '09:00' }}">
                                        </td>
                                        <td>
                                            <input type="time" class="form-control" name="days[{{ $day }}][end_time]"
                                                value="{{ $schedule ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i') : '17:00' }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary px-4">Update Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection