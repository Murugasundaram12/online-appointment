<div class="card-footer bg-white border-0 py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 small text-muted">
    <div class="d-flex flex-column flex-md-row align-items-center gap-2">
        <span class="text-muted">
            @if($paginator->total() > 0)
                Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} results
            @else
                No results
            @endif
        </span>
        @if(method_exists($paginator, 'perPage'))
            <div class="d-flex align-items-center gap-2">
                <span>Rows per page</span>
                <select class="form-select form-select-sm border-0 bg-light py-1" style="width: auto;" onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value)">
                    @php
                        $perPage = request('per_page', 10);
                        $perPageOptions = [10, 25, 50, 100];
                    @endphp
                    @foreach($perPageOptions as $option)
                        <option value="{{ request()->fullUrlWithQuery(['per_page' => $option, 'page' => 1]) }}" {{ $perPage == $option ? 'selected' : '' }}>{{$option}}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>
    <div class="pagination-wrapper d-flex justify-content-center w-100 w-md-auto">
        {{ $paginator->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
</div>
