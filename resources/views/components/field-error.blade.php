@props(['field'])

@error($field)
    <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
        {{ $message }}
    </div>
@enderror
