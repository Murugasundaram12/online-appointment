<div class="card-footer bg-white border-0 py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 small text-muted">
    <span>
        @if($paginator->total() > 0)
            Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} entries
        @else
            No entries
        @endif
    </span>
    <div class="pagination-wrapper d-flex justify-content-center w-100 w-md-auto">
        {{ $paginator->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
</div>
