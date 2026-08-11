@props([
    'paginator',
    'searchAction' => null,
    'searchName' => 'search',
    'searchValue' => null,
    'searchPlaceholder' => 'Search',
    'showSearch' => true,
    'showPerPage' => true,
    'toolbarClass' => null,
])

@php
    $perPage = request('per_page', $paginator->perPage());
    $perPageOptions = [10, 25, 50, 100];
    $searchValue = $searchValue ?? request('search');
@endphp

<div class="card border-0 shadow-sm mb-3 {{ $toolbarClass }}">
    <div class="card-body py-3">
        <form method="GET" action="{{ $searchAction ?? request()->url() }}" class="d-flex flex-column flex-xl-row gap-2 gap-xl-3 align-items-stretch align-items-xl-center">
            @if($showSearch)
                <div class="flex-grow-1" style="max-width: 380px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class='bx bx-search text-muted'></i></span>
                        <input type="text" name="{{ $searchName }}" value="{{ $searchValue }}" class="form-control border-start-0 ps-0" placeholder="{{ $searchPlaceholder }}">
                    </div>
                </div>
            @endif
            @if($showPerPage)
                <input type="hidden" name="per_page" value="{{ $perPage }}">
            @endif
            {{ $formExtra ?? '' }}
            <div class="d-flex flex-wrap gap-2 align-items-center">
                {{ $filters ?? '' }}
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center ms-xl-auto justify-content-between">
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
