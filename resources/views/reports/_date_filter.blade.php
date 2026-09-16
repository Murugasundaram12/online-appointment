@php
    $extraFields = $extraFields ?? [];
    $textFields = $textFields ?? [];
    $perPage = request('per_page', $paginator->perPage());
    $perPageOptions = [10, 25, 50, 100];
    $exportQuery = array_filter(request()->only(array_merge(['start_date', 'end_date'], array_keys($textFields), array_keys($extraFields))));
    $hasActiveFilters = request()->has('start_date') || request()->has('end_date') ||
        collect(array_keys($textFields))->some(fn($k) => request()->filled($k)) ||
        collect(array_keys($extraFields))->some(fn($k) => request()->filled($k));
@endphp
<div class="card list-toolbar-card border-0 shadow-sm mb-3" style="overflow: visible; position: relative; z-index: 20;">
    <div class="card-body p-3" style="overflow: visible;">
        <form method="GET" class="d-flex flex-wrap gap-2 gap-md-3 align-items-center justify-content-between m-0">
            <input type="hidden" name="per_page" value="{{ $perPage }}">
            <div class="d-flex gap-2">
                <div class="d-flex flex-column flex-md-row gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted text-nowrap">From</span>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $start->format('Y-m-d') }}">
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted text-nowrap">To</span>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $end->format('Y-m-d') }}">
                    </div>
                </div>
            </div>
            @foreach($textFields as $name => $label)
                <div class="flex-grow-1" style="max-width: 280px;">
                    <input type="text" name="{{ $name }}" class="form-control form-control-sm" value="{{ request($name) }}" placeholder="{{ $label }}">
                </div>
            @endforeach
            @foreach($extraFields as $name => $options)
                <div class="d-flex align-items-center gap-2">
                    <select name="{{ $name }}" class="form-select form-select-sm" style="width: auto;">
                        <option value="">{{ $options['label'] }}: All</option>
                        @foreach($options['items'] as $value => $label)
                            <option value="{{ $value }}" {{ request($name) == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm">Run Report</button>
                @if($hasActiveFilters)
                    <a href="{{ url()->current() }}" class="btn btn-light border btn-sm" title="Reset filters">
                        <i class='bx bx-reset me-1'></i>Reset
                    </a>
                @endif
                <a href="{{ route('reports.export', ['type' => $exportType] + $exportQuery) }}" class="btn btn-light border btn-sm">
                    <i class='bx bx-download me-1'></i>CSV
                </a>
            </div>
            <div class="d-flex align-items-center gap-2 small text-muted ms-auto">
                <span class="text-nowrap">Rows per page</span>
                <select class="form-select form-select-sm border-0 bg-light py-1" style="width: auto;" onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value)">
                    @foreach($perPageOptions as $option)
                        <option value="{{ request()->fullUrlWithQuery(['per_page' => $option, 'page' => 1]) }}" {{ $perPage == $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>
