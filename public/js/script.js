document.addEventListener("DOMContentLoaded", function () {
    const toastContainer = document.querySelector(".app-toast-container");

    const iconMap = {
        success: "bx-check-circle",
        danger: "bx-error-circle",
        error: "bx-error-circle",
        warning: "bx-error",
        info: "bx-info-circle"
    };

    window.AppToast = {
        show({ type = "info", title = "Notice", message = "", delay = 4200 } = {}) {
            if (!toastContainer || !window.bootstrap) return;
            const normalizedType = type === "error" ? "danger" : type;
            const toastEl = document.createElement("div");
            toastEl.className = `toast app-toast app-toast-${normalizedType}`;
            toastEl.setAttribute("role", normalizedType === "danger" ? "alert" : "status");
            toastEl.setAttribute("aria-live", normalizedType === "danger" ? "assertive" : "polite");
            toastEl.setAttribute("aria-atomic", "true");

            const header = document.createElement("div");
            header.className = "toast-header";

            const mark = document.createElement("span");
            mark.className = "app-toast-mark";
            mark.setAttribute("aria-hidden", "true");
            const icon = document.createElement("i");
            icon.className = `bx ${iconMap[normalizedType] || iconMap.info}`;
            mark.appendChild(icon);

            const strong = document.createElement("strong");
            strong.className = "me-auto";
            strong.textContent = title;

            const close = document.createElement("button");
            close.type = "button";
            close.className = "btn-close";
            close.setAttribute("data-bs-dismiss", "toast");
            close.setAttribute("aria-label", "Close");

            const body = document.createElement("div");
            body.className = "toast-body";
            body.textContent = message;

            header.append(mark, strong, close);
            toastEl.append(header, body);
            toastContainer.appendChild(toastEl);

            const toast = new bootstrap.Toast(toastEl, { delay, autohide: delay > 0 });
            toastEl.addEventListener("hidden.bs.toast", () => toastEl.remove());
            toast.show();
        }
    };

    window.AppButtonLoading = {
        set(button, loadingText = "Processing...") {
            if (!button || button.dataset.loading === "1") return;
            button.dataset.loading = "1";
            button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = `<span class="app-btn-spinner" aria-hidden="true"></span>${loadingText}`;
        },
        reset(button) {
            if (!button || button.dataset.loading !== "1") return;
            button.innerHTML = button.dataset.originalHtml || button.textContent;
            button.disabled = false;
            delete button.dataset.loading;
        }
    };

    const confirmModalEl = document.getElementById("appConfirmModal");
    const confirmModal = confirmModalEl && window.bootstrap ? new bootstrap.Modal(confirmModalEl) : null;
    const confirmTitle = document.getElementById("appConfirmTitle");
    const confirmSubtitle = document.getElementById("appConfirmSubtitle");
    const confirmMessage = document.getElementById("appConfirmMessage");
    const confirmRecord = document.getElementById("appConfirmRecord");
    const confirmButton = document.getElementById("appConfirmButton");
    const confirmIcon = document.getElementById("appConfirmIcon");
    let confirmCallback = null;
    let confirmTrigger = null;

    window.AppConfirm = {
        open(options = {}) {
            if (!confirmModal) {
                if (typeof options.onConfirm === "function") options.onConfirm();
                return;
            }
            const {
                title = "Confirm action",
                subtitle = "Please review before continuing.",
                message = "Are you sure?",
                record = "",
                confirmText = "Confirm",
                confirmClass = "btn-danger",
                type = "danger",
                onConfirm = null,
                trigger = document.activeElement
            } = options;

            confirmCallback = onConfirm;
            confirmTrigger = trigger;
            if (confirmTitle) confirmTitle.textContent = title;
            if (confirmSubtitle) confirmSubtitle.textContent = subtitle;
            if (confirmMessage) confirmMessage.textContent = message;
            if (confirmRecord) {
                confirmRecord.textContent = record || "";
                confirmRecord.classList.toggle("d-none", !record);
            }
            if (confirmButton) {
                confirmButton.textContent = confirmText;
                confirmButton.className = `btn ${confirmClass}`;
                confirmButton.disabled = false;
            }
            if (confirmIcon) {
                confirmIcon.className = `modal-icon modal-icon-${type === "danger" ? "danger" : type}`;
                confirmIcon.innerHTML = `<i class="bx ${type === "danger" ? "bx-trash" : "bx-error-circle"}"></i>`;
            }
            confirmModal.show();
        }
    };

    if (confirmButton) {
        confirmButton.addEventListener("click", () => {
            const callback = confirmCallback;
            confirmCallback = null;
            if (typeof callback === "function") callback(confirmButton);
        });
    }
    if (confirmModalEl) {
        confirmModalEl.addEventListener("hidden.bs.modal", () => {
            confirmCallback = null;
            if (confirmTrigger && typeof confirmTrigger.focus === "function") confirmTrigger.focus();
            confirmTrigger = null;
            if (confirmButton) window.AppButtonLoading.reset(confirmButton);
        });
    }

    document.querySelectorAll("[data-app-alert-type]").forEach((alertEl) => {
        const message = alertEl.textContent.replace(/\s+/g, " ").trim();
        if (message) {
            window.AppToast.show({
                type: alertEl.dataset.appAlertType || "info",
                title: alertEl.dataset.appAlertTitle || "Notice",
                message
            });
        }
    });

    const toggle = document.querySelector("[data-sidebar-toggle]");
    if (toggle) {
        toggle.addEventListener("click", () => document.body.classList.toggle("sidebar-open"));
    }

    document.addEventListener("click", (event) => {
        if (document.body.classList.contains("sidebar-open") && event.target === document.body) {
            document.body.classList.remove("sidebar-open");
        }
    });

    document.querySelectorAll("form").forEach((form) => {
        form.addEventListener("submit", (event) => {
            if (form.dataset.appManaged === "true") return;

            if (form.dataset.confirm && form.dataset.confirmed !== "1") {
                event.preventDefault();
                window.AppConfirm.open({
                    title: form.dataset.confirmTitle || "Confirm action",
                    subtitle: form.dataset.confirmSubtitle || "This action needs confirmation.",
                    message: form.dataset.confirm || "Are you sure?",
                    record: form.dataset.confirmRecord || "",
                    confirmText: form.dataset.confirmText || "Confirm",
                    confirmClass: form.dataset.confirmClass || "btn-danger",
                    type: form.dataset.confirmType || "danger",
                    trigger: event.submitter,
                    onConfirm: (button) => {
                        window.AppButtonLoading.set(button, form.dataset.confirmLoading || "Processing...");
                        form.dataset.confirmed = "1";
                        form.requestSubmit ? form.requestSubmit() : form.submit();
                    }
                });
                return;
            }

            const submitter = event.submitter || form.querySelector('button[type="submit"], button:not([type])');
            if (submitter && !submitter.dataset.keepEnabled) {
                window.AppButtonLoading.set(submitter, submitter.dataset.loadingText || "Saving...");
            }
        });
    });

    const ctxTrend = document.getElementById("bookingTrendChart");
    if (ctxTrend && window.Chart) {
        new Chart(ctxTrend.getContext("2d"), {
            type: "bar",
            data: window.dashboardAppointmentChart || { labels: [], datasets: [] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: "top", align: "end", labels: { usePointStyle: true, boxWidth: 8 } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: "#eef2f7" } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    const ctxChannel = document.getElementById("bookingChannelChart");
    if (ctxChannel && window.Chart) {
        new Chart(ctxChannel.getContext("2d"), {
            type: "doughnut",
            data: window.dashboardStatusChart || { labels: [], datasets: [] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "72%",
                plugins: { legend: { display: false } }
            }
        });
    }

    function initPasswordToggles() {
        document.querySelectorAll('input[type="password"]').forEach((input) => {
            if (input.dataset.passwordToggleInit === "1") return;
            input.dataset.passwordToggleInit = "1";

            const parent = input.parentElement;
            let button = parent ? parent.querySelector('.js-toggle-password-btn, #togglePassword') : null;

            if (!button) {
                const wrapper = document.createElement('div');
                wrapper.className = 'input-group';
                input.parentNode.insertBefore(wrapper, input);
                wrapper.appendChild(input);

                button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-outline-secondary js-toggle-password-btn';
                button.setAttribute('aria-label', 'Toggle password visibility');
                button.innerHTML = '<i class="bx bx-show"></i>';
                wrapper.appendChild(button);
            }

            button.addEventListener('click', () => {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                const icon = button.querySelector('i');
                if (icon) {
                    icon.className = isPassword ? 'bx bx-hide' : 'bx bx-show';
                }
            });
        });
    }
    initPasswordToggles();
    document.addEventListener('shown.bs.modal', initPasswordToggles);

    function formatCanadianPhone(value) {
        const digits = String(value || '').replace(/\D/g, '');
        if (digits.length === 10) {
            return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
        }
        if (digits.length === 11 && digits.startsWith('1')) {
            return `(${digits.slice(1, 4)}) ${digits.slice(4, 7)}-${digits.slice(7)}`;
        }
        return value;
    }

    document.addEventListener('blur', (e) => {
        if (e.target && (e.target.classList.contains('js-phone-input') || e.target.name === 'phone' || e.target.name === 'emergency_phone' || e.target.name === 'alternate_phone')) {
            e.target.value = formatCanadianPhone(e.target.value);
        }
    }, true);
});
