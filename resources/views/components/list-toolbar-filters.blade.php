@props(['showClear' => false, 'clearUrl' => '', 'clearLabel' => 'Clear'])

@if($showClear && $clearUrl)
    <a href="{{ $clearUrl }}" class="btn btn-light border btn-sm text-muted">
        <i class='bx bx-x me-1'></i>{{ $clearLabel }}
    </a>
@endif
