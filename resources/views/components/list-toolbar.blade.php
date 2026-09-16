@props([
    'paginator',
    'searchAction' => null,
    'searchName' => 'search',
    'searchValue' => null,
    'searchPlaceholder' => 'Search',
    'showSearch' => true,
    'showPerPage' => true,
    'toolbarClass' => null,
    'ignoreParams' => [],
])

@php
    $perPage = request('per_page', $paginator->perPage());
    $perPageOptions = [10, 25, 50, 100];
    $searchValue = $searchValue ?? request('search');
    $hiddenParams = request()->except(array_merge([$searchName, 'page', 'per_page'], (array) $ignoreParams));
@endphp

<div class="card list-toolbar-card border-0 shadow-sm mb-3 {{ $toolbarClass }}" style="overflow: visible; position: relative; z-index: 20;">
    <div class="card-body p-3" style="overflow: visible;">
        <form method="GET" action="{{ $searchAction ?? request()->url() }}" class="d-flex flex-wrap gap-2 gap-md-3 align-items-center justify-content-between m-0">
            @if($showPerPage)
                <input type="hidden" name="per_page" value="{{ $perPage }}">
            @endif
            @foreach($hiddenParams as $paramKey => $paramValue)
                @if(is_array($paramValue))
                    @foreach($paramValue as $nestedValue)
                        <input type="hidden" name="{{ $paramKey }}[]" value="{{ $nestedValue }}">
                    @endforeach
                @elseif($paramValue !== null && $paramValue !== '')
                    <input type="hidden" name="{{ $paramKey }}" value="{{ $paramValue }}">
                @endif
            @endforeach

            <!-- Left Group: Search input + Filters -->
            <div class="d-flex flex-wrap gap-2 align-items-center flex-grow-1 min-w-0">
                @if($showSearch)
                    <div style="min-width: 220px; max-width: 340px;" class="flex-grow-1 flex-sm-grow-0">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class='bx bx-search text-muted'></i></span>
                            <input type="text" name="{{ $searchName }}" value="{{ $searchValue }}" class="form-control border-start-0 ps-0" placeholder="{{ $searchPlaceholder }}">
                        </div>
                    </div>
                @endif
                {{ $formExtra ?? '' }}
                {{ $filters ?? '' }}
            </div>

            <!-- Right Group: Rows per page + Action buttons -->
            <div class="d-flex flex-wrap gap-2 align-items-center ms-auto justify-content-end">
                @if($showPerPage)
                    <div class="d-flex align-items-center gap-2 small text-muted">
                        <span class="text-nowrap">Rows per page</span>
                        <select class="form-select form-select-sm border-0 bg-light py-1" style="width: auto;" onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value)">
                            @foreach($perPageOptions as $option)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $option, 'page' => 1]) }}" {{ $perPage == $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                {{ $actions ?? '' }}
            </div>
        </form>
    </div>
</div>
