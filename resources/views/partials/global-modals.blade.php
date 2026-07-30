<div class="toast-container app-toast-container position-fixed top-0 end-0 p-3" aria-live="polite" aria-atomic="true"></div>

<div class="modal fade app-modal app-confirm-modal" id="appConfirmModal" tabindex="-1" aria-labelledby="appConfirmTitle" aria-describedby="appConfirmMessage" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-heading">
                    <div class="modal-icon modal-icon-warning" id="appConfirmIcon" aria-hidden="true">
                        <i class="bx bx-error-circle"></i>
                    </div>
                    <div>
                        <h5 class="modal-title" id="appConfirmTitle">Confirm action</h5>
                        <p class="modal-subtitle" id="appConfirmSubtitle">Please review before continuing.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="app-confirm-message" id="appConfirmMessage">Are you sure?</p>
                <div class="app-confirm-record d-none" id="appConfirmRecord"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" id="appConfirmCancel">Cancel</button>
                <button type="button" class="btn btn-danger" id="appConfirmButton">Confirm</button>
            </div>
        </div>
    </div>
</div>
