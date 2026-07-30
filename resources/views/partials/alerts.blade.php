<div class="container-fluid pt-3">
    @if(session('success'))
        <div class="alert app-alert app-alert-success alert-success alert-dismissible fade show" role="alert" data-app-alert-type="success" data-app-alert-title="Success">
            <i class="bx bx-check-circle" aria-hidden="true"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert app-alert app-alert-danger alert-danger alert-dismissible fade show" role="alert" data-app-alert-type="danger" data-app-alert-title="Error">
            <i class="bx bx-error-circle" aria-hidden="true"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert app-alert app-alert-danger alert-danger alert-dismissible fade show" role="alert" data-app-alert-type="danger" data-app-alert-title="Please check the form">
            <i class="bx bx-error-circle" aria-hidden="true"></i>
            <div><strong>Please check the form.</strong> {{ $errors->first() }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
</div>
