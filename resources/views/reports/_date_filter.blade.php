@php
    $extraFields = $extraFields ?? [];
    $textFields = $textFields ?? [];
@endphp
<form method="GET" class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Start date</label>
                <input type="date" name="start_date" class="form-control" value="{{ $start->format('Y-m-d') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">End date</label>
                <input type="date" name="end_date" class="form-control" value="{{ $end->format('Y-m-d') }}">
            </div>
            @foreach($textFields as $name => $label)
                <div class="col-md-3">
                    <label class="form-label">{{ $label }}</label>
                    <input type="text" name="{{ $name }}" class="form-control" value="{{ request($name) }}" placeholder="{{ $label }}">
                </div>
            @endforeach
            @foreach($extraFields as $name => $options)
                <div class="col-md-3">
                    <label class="form-label">{{ $options['label'] }}</label>
                    <select name="{{ $name }}" class="form-select">
                        <option value="">All</option>
                        @foreach($options['items'] as $value => $label)
                            <option value="{{ $value }}" {{ request($name) == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            <div class="col-md d-flex gap-2">
                <button class="btn btn-primary">Run Report</button>
                @php
                    $exportQuery = array_filter(request()->only(array_merge(['start_date', 'end_date'], array_keys($textFields), array_keys($extraFields))));
                @endphp
                <a href="{{ route('reports.export', ['type' => $exportType] + $exportQuery) }}" class="btn btn-light border">
                    <i class='bx bx-download me-1'></i>CSV
                </a>
            </div>
        </div>
    </div>
</form>
