<div class="container-fluid pt-3 d-none">
    @if(session('success'))
        <div class="alert app-alert app-alert-success alert-success alert-dismissible fade show" role="alert"
             data-app-alert-type="success"
             data-app-alert-title="Success"
             data-app-alert-message="{{ session('success') }}">
            <i class="bx bx-check-circle" aria-hidden="true"></i>
            <div class="app-alert-message">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert app-alert app-alert-danger alert-danger alert-dismissible fade show" role="alert"
             data-app-alert-type="danger"
             data-app-alert-title="Error"
             data-app-alert-message="{{ session('error') }}">
            <i class="bx bx-error-circle" aria-hidden="true"></i>
            <div class="app-alert-message">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert app-alert app-alert-warning alert-warning alert-dismissible fade show" role="alert"
             data-app-alert-type="warning"
             data-app-alert-title="Warning"
             data-app-alert-message="{{ session('warning') }}">
            <i class="bx bx-error" aria-hidden="true"></i>
            <div class="app-alert-message">{{ session('warning') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert app-alert app-alert-info alert-info alert-dismissible fade show" role="alert"
             data-app-alert-type="info"
             data-app-alert-title="Notice"
             data-app-alert-message="{{ session('info') }}">
            <i class="bx bx-info-circle" aria-hidden="true"></i>
            <div class="app-alert-message">{{ session('info') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('status'))
        <div class="alert app-alert app-alert-info alert-info alert-dismissible fade show" role="alert"
             data-app-alert-type="info"
             data-app-alert-title="Status"
             data-app-alert-message="{{ session('status') }}">
            <i class="bx bx-info-circle" aria-hidden="true"></i>
            <div class="app-alert-message">{{ session('status') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
</div>
