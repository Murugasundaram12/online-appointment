(function () {
    // Globally prevent native browser validation tooltips (e.g. "Please fill in this field.")
    document.addEventListener("invalid", (e) => {
        e.preventDefault();
    }, true);

    function initApp() {
    const toastContainer = document.querySelector(".app-toast-container");

    const iconMap = {
        success: "bx-check-circle",
        danger: "bx-error-circle",
        error: "bx-error-circle",
        warning: "bx-error",
        info: "bx-info-circle"
    };

    window.AppToast = {
        show({ type = "info", title, message = "", delay = 5500 } = {}) {
            let container = document.querySelector(".app-toast-container");
            if (!container) {
                container = document.createElement("div");
                container.className = "toast-container app-toast-container position-fixed top-0 end-0 p-3";
                container.setAttribute("aria-live", "polite");
                container.setAttribute("aria-atomic", "true");
                document.body.appendChild(container);
            }
            if (!window.bootstrap || !window.bootstrap.Toast) return;

            const normalizedType = (type === "error" || type === "danger") ? "danger" : type;
            const defaultTitles = {
                danger: "Error",
                success: "Success",
                warning: "Warning",
                info: "Notice"
            };
            const resolvedTitle = title || defaultTitles[normalizedType] || "Notice";

            const toastEl = document.createElement("div");
            toastEl.className = `toast app-toast app-toast-${normalizedType}`;
            toastEl.setAttribute("role", normalizedType === "danger" ? "alert" : "status");
            toastEl.setAttribute("aria-live", normalizedType === "danger" ? "assertive" : "polite");
            toastEl.setAttribute("aria-atomic", "true");

            const card = document.createElement("div");
            card.className = "app-toast-card";

            const iconBox = document.createElement("div");
            iconBox.className = "app-toast-icon-box";
            iconBox.setAttribute("aria-hidden", "true");
            const icon = document.createElement("i");
            icon.className = `bx ${iconMap[normalizedType] || iconMap.info}`;
            iconBox.appendChild(icon);

            const content = document.createElement("div");
            content.className = "app-toast-content";

            const titleEl = document.createElement("div");
            titleEl.className = "app-toast-title";
            titleEl.textContent = resolvedTitle;

            const messageEl = document.createElement("div");
            messageEl.className = "app-toast-message";
            messageEl.textContent = message;

            content.appendChild(titleEl);
            content.appendChild(messageEl);

            const closeBtn = document.createElement("button");
            closeBtn.type = "button";
            closeBtn.className = "btn-close app-toast-close";
            closeBtn.setAttribute("data-bs-dismiss", "toast");
            closeBtn.setAttribute("aria-label", "Close");

            card.appendChild(iconBox);
            card.appendChild(content);
            card.appendChild(closeBtn);

            toastEl.appendChild(card);
            container.appendChild(toastEl);

            const toast = new bootstrap.Toast(toastEl, { delay, autohide: delay > 0 });
            toastEl.addEventListener("hidden.bs.toast", () => toastEl.remove());
            toast.show();
            return toast;
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

    const shownAlerts = new Set();
    document.querySelectorAll("[data-app-alert-type]").forEach((alertEl) => {
        const message = alertEl.dataset.appAlertMessage ||
            alertEl.querySelector('.app-alert-message')?.textContent?.trim() ||
            alertEl.querySelector('div:not([class])')?.textContent?.trim() ||
            alertEl.textContent.replace(/\s+/g, " ").trim();
        const type = alertEl.dataset.appAlertType || "info";
        const key = `${type}:${message}`;
        if (message && !shownAlerts.has(key)) {
            shownAlerts.add(key);
            window.AppToast.show({
                type: type,
                title: alertEl.dataset.appAlertTitle || (type === "danger" ? "Error" : "Notice"),
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

    window.AppFormErrors = {
        clear(form) {
            if (!form) return;
            form.querySelectorAll('.app-field-error').forEach(el => el.remove());
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.ts-wrapper.is-invalid, .select2-container.is-invalid, .select2.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        },

        getLabelForField(ctrl) {
            if (!ctrl) return 'This field';
            let labelText = '';

            // 1. Direct label[for="ctrl.id"]
            if (ctrl.id) {
                const label = ctrl.form ? ctrl.form.querySelector(`label[for="${ctrl.id}"]`) : document.querySelector(`label[for="${ctrl.id}"]`);
                if (label) labelText = label.textContent;
            }

            // 2. Look for label in parent container
            if (!labelText) {
                const container = ctrl.closest('.field-content, .field-group, .form-group, .mb-3, .mb-2, .col-md-6, .col-md-4, .col-md-3, .col-md-2, .col-md-12, .col-12, .col');
                if (container) {
                    const label = container.querySelector('label');
                    if (label) labelText = label.textContent;
                }
            }

            // 3. Parent label
            if (!labelText) {
                const parentLabel = ctrl.closest('label');
                if (parentLabel) {
                    const clone = parentLabel.cloneNode(true);
                    clone.querySelectorAll('input, select, textarea, button').forEach(n => n.remove());
                    labelText = clone.textContent;
                }
            }

            // 4. aria-label or placeholder
            if (!labelText) {
                labelText = ctrl.getAttribute('aria-label') || '';
            }

            if (!labelText) {
                const ph = ctrl.getAttribute('placeholder') || '';
                if (ph) {
                    labelText = ph.replace(/^(Enter|Select|Type|Input)\s+/i, '');
                }
            }

            // 5. Fallback to name or id
            if (!labelText) {
                const rawName = ctrl.name || ctrl.id || '';
                if (rawName) {
                    labelText = rawName.replace(/\[.*\]/g, '').replace(/[_-]/g, ' ');
                }
            }

            // Clean up: remove asterisks, colons, extra whitespace
            labelText = labelText.replace(/[*:]/g, '').replace(/\s+/g, ' ').trim();
            if (labelText) {
                const lower = labelText.toLowerCase();
                const naturalLabels = {
                    'start time': 'Start time',
                    'end time': 'End time',
                    'service name': 'Service name',
                    'first name': 'First name',
                    'last name': 'Last name',
                    'payment method': 'Payment method',
                    'method': 'Payment method',
                    'invoice': 'Invoice',
                    'recurrence type': 'Recurrence type',
                    'location': 'Location',
                    'staff': 'Staff',
                    'date': 'Date',
                    'start date': 'Start date',
                    'end date': 'End date',
                    'amount': 'Amount'
                };
                if (naturalLabels[lower]) {
                    labelText = naturalLabels[lower];
                } else {
                    labelText = labelText.charAt(0).toUpperCase() + labelText.slice(1);
                }
            }
            return labelText || 'This field';
        },

        findField(form, fieldName) {
            if (!form || !fieldName) return null;

            // Special field group targets
            if (fieldName === 'weekly_days' || fieldName === 'weekly_days[]') {
                const group = form.querySelector('.weekday-checkbox-group') || form.querySelector('#panel-weekly');
                if (group) return group;
            }
            if (fieldName === 'split_methods' || fieldName === 'split_methods[]') {
                const group = form.querySelector('#split-chk-cash')?.closest('.d-flex') || form.querySelector('#split-selection-warning')?.parentElement;
                if (group) return group;
            }
            if (fieldName === 'invoice_id') {
                const el = form.querySelector('#payment-invoice') || form.querySelector('[name="invoice_id"]');
                if (el) return el;
            }
            if (fieldName === 'payment_method') {
                const el = form.querySelector('#pmt-method-select') || form.querySelector('[name="payment_method"]');
                if (el) return el;
            }
            if (fieldName === 'payment_date') {
                const el = form.querySelector('#payment-date') || form.querySelector('[name="payment_date"]');
                if (el) return el;
            }
            if (fieldName === 'amount') {
                const el = form.querySelector('#payment-amount') || form.querySelector('[name="amount"]');
                if (el) return el;
            }
            if (fieldName === 'staff_id') {
                const el = form.querySelector('#staff_id') || form.querySelector('[name="staff_id"]') || form.querySelector('#cs-staff-name');
                if (el) return el;
            }
            if (fieldName === 'location_id') {
                const el = form.querySelector('#location_id') || form.querySelector('[name="location_id"]') || form.querySelector('#cs-recurrence-type');
                if (el) return el;
            }
            if (fieldName === 'working_date') {
                const el = form.querySelector('#working_date') || form.querySelector('#cs-working-date') || form.querySelector('[name="working_date"]');
                if (el) return el;
            }
            if (fieldName === 'start_date') {
                const el = form.querySelector('#start_date') || form.querySelector('#cs-start-date') || form.querySelector('[name="start_date"]');
                if (el) return el;
            }
            if (fieldName === 'end_date') {
                const el = form.querySelector('#end_date') || form.querySelector('#cs-end-date') || form.querySelector('[name="end_date"]');
                if (el) return el;
            }

            // 1. Direct name match
            let el = form.querySelector(`[name="${fieldName}"]`);
            if (el && el.type !== 'hidden') return el;

            // 2. Array name match, e.g. services[1][quantity] or split_methods[]
            el = form.querySelector(`[name="${fieldName}[]"]`);
            if (el) return el;

            // 3. Dot notation to array bracket: "submitted_data.notes" -> "submitted_data[notes]"
            if (fieldName.includes('.')) {
                const parts = fieldName.split('.');
                const bracketName = parts[0] + parts.slice(1).map(p => `[${p}]`).join('');
                el = form.querySelector(`[name="${bracketName}"]`);
                if (el) return el;
            }

            // 4. Exact ID
            el = form.querySelector(`#${fieldName}`);
            if (el) return el;

            // 5. Kebab case ID: "first_name" -> "#first-name"
            const kebab = fieldName.replace(/_/g, '-');
            el = form.querySelector(`#${kebab}`);
            if (el) return el;

            // 6. Appointment modal mapping: "staff_id" -> "#appt-staff", etc.
            const apptPrefixes = {
                'staff_id': '#appt-staff',
                'location_id': '#appt-location',
                'service_id': '#appt-service',
                'client_id': '#appt-client',
                'start_time': '#appt-start',
                'end_time': '#appt-end',
                'status': '#appt-status',
                'cancellation_reason': '#appt-cancellation-reason',
                'notes': '#appt-notes'
            };
            if (apptPrefixes[fieldName]) {
                el = form.querySelector(apptPrefixes[fieldName]);
                if (el) return el;
            }

            // 7. Field aliases
            const fieldAliases = {
                'date': ['working_date', 'booking_date', 'appointment_date', 'start_date'],
                'working_date': ['date', 'booking_date', 'start_date'],
                'start_date': ['date', 'working_date'],
                'service_name': ['name'],
                'name': ['service_name'],
                'service_category_id': ['category_id', 'category'],
                'category_id': ['service_category_id'],
                'appointment_id': ['appointment'],
                'appointment': ['appointment_id'],
                'location_id': ['location'],
                'location': ['location_id'],
                'staff_id': ['staff'],
                'staff': ['staff_id']
            };
            if (fieldAliases[fieldName]) {
                for (const alias of fieldAliases[fieldName]) {
                    const aliasEl = form.querySelector(`[name="${alias}"]`) ||
                                    form.querySelector(`[name="${alias}[]"]`) ||
                                    form.querySelector(`#${alias}`) ||
                                    form.querySelector(`#${alias.replace(/_/g, '-')}`) ||
                                    (apptPrefixes[alias] ? form.querySelector(apptPrefixes[alias]) : null);
                    if (aliasEl && aliasEl.type !== 'hidden') return aliasEl;
                }
            }

            // 8. Prefix with hyphen, e.g. "new-client-first-name" or "edit-service-name" or "cs-start-time"
            el = form.querySelector(`[id$="-${kebab}"]`) || form.querySelector(`[id*="${kebab}"]`);
            if (el && el.type !== 'hidden') return el;

            // Hidden fallback
            el = form.querySelector(`[name="${fieldName}"]`);
            if (el) return el;

            return null;
        },

        show(form, errors = {}) {
            if (!form || !errors) return [];
            this.clear(form);

            const unmapped = [];
            let count = 0;
            let firstInvalidInput = null;

            for (const [field, messages] of Object.entries(errors)) {
                const message = Array.isArray(messages) ? messages[0] : messages;
                if (!message) continue;

                const input = this.findField(form, field);
                if (!input) {
                    unmapped.push(message);
                    continue;
                }

                if (!firstInvalidInput && typeof input.focus === 'function' && input.type !== 'hidden') {
                    firstInvalidInput = input;
                }

                input.classList.add('is-invalid');

                // Determine target element to insert before (above)
                let target = input;
                if (input.classList.contains('weekday-checkbox-group') || input.id === 'panel-weekly') {
                    target = input;
                    form.querySelectorAll('input[name="weekly_days[]"]').forEach(cb => cb.classList.add('is-invalid'));
                } else if (input.classList.contains('split-method-chk') || input.querySelector?.('.split-method-chk')) {
                    target = input;
                    form.querySelectorAll('.split-method-chk').forEach(cb => cb.classList.add('is-invalid'));
                } else if (input.closest('.input-group')) {
                    target = input.closest('.input-group');
                } else if (input.closest('.ts-wrapper')) {
                    target = input.closest('.ts-wrapper');
                } else if (input.tomselect && input.tomselect.wrapper) {
                    target = input.tomselect.wrapper;
                } else if (input.nextElementSibling && input.nextElementSibling.classList.contains('ts-wrapper')) {
                    target = input.nextElementSibling;
                } else if (input.closest('.select2-container')) {
                    target = input.closest('.select2-container');
                } else if (input.nextElementSibling && (input.nextElementSibling.classList.contains('select2') || input.nextElementSibling.classList.contains('select2-container'))) {
                    target = input.nextElementSibling;
                }

                if (target !== input) {
                    target.classList.add('is-invalid');
                }
                if (input.tomselect && input.tomselect.wrapper) {
                    input.tomselect.wrapper.classList.add('is-invalid');
                }

                // Check if error already exists directly above
                const prev = target.previousElementSibling;
                if (prev && (prev.classList.contains('app-field-error') || prev.classList.contains('invalid-feedback')) && prev.getAttribute('data-error-field') === field) {
                    prev.textContent = message;
                    prev.classList.add('d-block');
                } else {
                    const errorEl = document.createElement('div');
                    errorEl.className = 'invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium';
                    errorEl.setAttribute('role', 'alert');
                    errorEl.setAttribute('data-error-field', field);
                    errorEl.textContent = message;
                    target.parentNode.insertBefore(errorEl, target);
                }
                count++;
            }
            this.attachAutoClear(form);

            if (firstInvalidInput && typeof firstInvalidInput.focus === 'function') {
                try {
                    firstInvalidInput.focus({ preventScroll: false });
                } catch (err) {}
            }

            unmapped.mappedCount = count;
            return unmapped;
        },

        validate(form) {
            if (!form) return true;
            this.clear(form);
            const errors = {};
            let hasErrors = false;

            // 1. Weekly schedule days check
            const recurrenceSelect = form.querySelector('[name="recurrence_type"]') || form.querySelector('#recurrence_type') || form.querySelector('#cs-recurrence-type');
            if (recurrenceSelect && recurrenceSelect.value === 'weekly') {
                const weeklyChecked = form.querySelectorAll('input[name="weekly_days[]"]:checked, .cs-weekly-day:checked');
                if (weeklyChecked.length === 0) {
                    errors['weekly_days'] = 'Please select at least one day for weekly recurrence.';
                    hasErrors = true;
                }
            }

            // 2. Payment split methods check
            const pmtMethod = form.querySelector('#pmt-method-select') || form.querySelector('[name="payment_method"]');
            if (pmtMethod && pmtMethod.value === 'both') {
                const splitChecked = form.querySelectorAll('.split-method-chk:checked');
                if (splitChecked.length < 2) {
                    errors['split_methods'] = 'Please select at least two payment methods for Split Payment.';
                    hasErrors = true;
                }
            }

            const controls = form.querySelectorAll('input, select, textarea');
            controls.forEach((ctrl) => {
                if (ctrl.disabled || ctrl.type === 'hidden' || ctrl.type === 'submit' || ctrl.type === 'button' || ctrl.type === 'reset') {
                    return;
                }

                // Skip controls in hidden panels/tabs
                if (ctrl.closest('.d-none') || ctrl.closest('[hidden]')) {
                    return;
                }

                const fieldName = ctrl.name || ctrl.id;
                if (!fieldName || errors[fieldName]) return;

                const label = this.getLabelForField(ctrl);
                const val = (ctrl.value || '').trim();

                // Required check
                if (ctrl.hasAttribute('required') || ctrl.required) {
                    if (ctrl.type === 'checkbox') {
                        if (!ctrl.checked) {
                            errors[fieldName] = `${label} is required.`;
                            hasErrors = true;
                            return;
                        }
                    } else if (ctrl.type === 'radio') {
                        const checkedRadio = form.querySelector(`input[type="radio"][name="${ctrl.name}"]:checked`);
                        if (!checkedRadio) {
                            errors[fieldName] = `${label} is required.`;
                            hasErrors = true;
                            return;
                        }
                    } else if (!val) {
                        errors[fieldName] = `${label} is required.`;
                        hasErrors = true;
                        return;
                    }
                }

                // Number input check: amount must be > 0 if required or if min="0.01"
                if (ctrl.type === 'number') {
                    const num = parseFloat(val);
                    if (ctrl.name === 'amount' && ctrl.form && ctrl.form.id === 'payment-record-form') {
                        if (val === '') {
                            errors[fieldName] = 'Amount is required.';
                            hasErrors = true;
                            return;
                        } else if (isNaN(num)) {
                            errors[fieldName] = 'Amount must be a number.';
                            hasErrors = true;
                            return;
                        } else if (num <= 0) {
                            errors[fieldName] = 'Paid amount must be greater than 0.';
                            hasErrors = true;
                            return;
                        }
                    } else if (ctrl.hasAttribute('min') && val !== '' && !isNaN(num)) {
                        const min = parseFloat(ctrl.getAttribute('min'));
                        if (min > 0 && num < min) {
                            errors[fieldName] = `${label} must be greater than 0.`;
                            hasErrors = true;
                            return;
                        }
                    }
                }

                // Email format check if filled
                if (ctrl.type === 'email' && val) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(val)) {
                        errors[fieldName] = `${label} must be a valid email address.`;
                        hasErrors = true;
                        return;
                    }
                }

                // Time comparison check: end_time must be after start_time if both given
                if (ctrl.name === 'end_time' && val) {
                    const startCtrl = form.querySelector('[name="start_time"]') || form.querySelector('#start_time') || form.querySelector('#cs-start-time');
                    if (startCtrl && startCtrl.value && val <= startCtrl.value) {
                        errors[fieldName] = 'End time must be after start time.';
                        hasErrors = true;
                        return;
                    }
                }

                // Date comparison check: end_date must be on or after start_date if both given
                if (ctrl.name === 'end_date' && val) {
                    const startCtrl = form.querySelector('[name="start_date"]') || form.querySelector('#start_date') || form.querySelector('#cs-start-date');
                    if (startCtrl && startCtrl.value && val < startCtrl.value) {
                        errors[fieldName] = 'End date must be on or after start date.';
                        hasErrors = true;
                        return;
                    }
                }
            });

            if (hasErrors) {
                this.show(form, errors);
                return false;
            }

            this.clear(form);
            return true;
        },

        attachAutoClear(form) {
            if (!form || form.dataset.errorClearBound === '1') return;
            form.dataset.errorClearBound = '1';

            const clearForElement = (el) => {
                if (!el) return;
                el.classList.remove('is-invalid');
                let target = el.closest('.input-group') || el.closest('.ts-wrapper') || el.closest('.select2-container') || (el.nextElementSibling && (el.nextElementSibling.classList.contains('ts-wrapper') || el.nextElementSibling.classList.contains('select2') || el.nextElementSibling.classList.contains('select2-container')) ? el.nextElementSibling : el);
                if (target !== el) {
                    target.classList.remove('is-invalid');
                }
                if (el.tomselect && el.tomselect.wrapper) {
                    el.tomselect.wrapper.classList.remove('is-invalid');
                }
                let prev = target.previousElementSibling;
                if (prev && (prev.classList.contains('app-field-error') || prev.classList.contains('invalid-feedback'))) {
                    prev.remove();
                }
                const fieldName = (el.name || el.id || '').replace(/\[\]$/, '');
                if (fieldName) {
                    form.querySelectorAll(`[data-error-field="${fieldName}"], [data-error-field="${fieldName}[]"]`).forEach(err => err.remove());
                }
            };

            form.addEventListener('input', (e) => clearForElement(e.target), true);
            form.addEventListener('change', (e) => {
                clearForElement(e.target);
                if (e.target.name === 'weekly_days[]' || e.target.classList.contains('cs-weekly-day')) {
                    form.querySelectorAll('[data-error-field="weekly_days"]').forEach(err => err.remove());
                    form.querySelectorAll('input[name="weekly_days[]"], .cs-weekly-day').forEach(cb => cb.classList.remove('is-invalid'));
                }
                if (e.target.classList.contains('split-method-chk')) {
                    form.querySelectorAll('[data-error-field="split_methods"]').forEach(err => err.remove());
                    form.querySelectorAll('.split-method-chk').forEach(cb => cb.classList.remove('is-invalid'));
                }
                if (e.target.name === 'recurrence_type' || e.target.id === 'recurrence_type' || e.target.id === 'cs-recurrence-type') {
                    const type = e.target.value;
                    if (type === 'one_time') {
                        form.querySelectorAll('[data-error-field="start_date"], [data-error-field="end_date"], [data-error-field="weekly_days"]').forEach(err => err.remove());
                    } else {
                        form.querySelectorAll('[data-error-field="working_date"], [data-error-field="date"]').forEach(err => err.remove());
                    }
                }
            }, true);
        }
    };

    function bindFormSubmit(form) {
        if (!form || form.dataset.submitBound === "1") return;
        form.dataset.submitBound = "1";

        form.setAttribute("novalidate", "");
        form.noValidate = true;

        window.AppFormErrors?.attachAutoClear(form);

        form.addEventListener("submit", (event) => {
            if (form.dataset.appManaged === "true") return;

            // Run client-side validation for non-GET forms
            if (form.method.toLowerCase() !== "get" && form.dataset.noClientValidate !== "true") {
                if (window.AppFormErrors && !window.AppFormErrors.validate(form)) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    return;
                }
            }

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
    }

    document.querySelectorAll("form").forEach(bindFormSubmit);

    document.addEventListener("hidden.bs.modal", (e) => {
        if (e.target && window.AppFormErrors) {
            window.AppFormErrors.clear(e.target);
            e.target.querySelectorAll("form").forEach(modalForm => {
                window.AppFormErrors.clear(modalForm);
            });
        }
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
        // Handle all explicit toggle buttons
        document.querySelectorAll('.js-toggle-password-btn, #togglePassword, [data-password-toggle]').forEach((button) => {
            if (button.dataset.toggleBound === "1") return;
            button.dataset.toggleBound = "1";

            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const targetSelector = button.getAttribute('data-target');
                let input = targetSelector ? document.querySelector(targetSelector) : null;
                if (!input) {
                    const parent = button.closest('.input-group') || button.parentElement;
                    input = parent ? parent.querySelector('input') : null;
                }
                if (!input) return;

                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';

                const icon = button.querySelector('i');
                if (icon) {
                    if (isPassword) {
                        icon.className = 'bx bx-hide';
                    } else {
                        icon.className = 'bx bx-show';
                    }
                }
                button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            });
        });

        // Auto-wrap any standalone password inputs that don't have a toggle button
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
                button.setAttribute('aria-label', 'Show password');
                button.innerHTML = '<i class="bx bx-show"></i>';
                wrapper.appendChild(button);

                button.dataset.toggleBound = "1";
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.className = isPassword ? 'bx bx-hide' : 'bx bx-show';
                    }
                    button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                });
            }
        });
    }
    initPasswordToggles();
    document.addEventListener('shown.bs.modal', initPasswordToggles);

    function formatCanadianPhone(value) {
        if (!value) return '';
        let digits = String(value).replace(/\D/g, '');
        if (digits.length === 11 && digits.startsWith('1')) {
            digits = digits.slice(1);
        }
        if (digits.length > 10) {
            digits = digits.slice(0, 10);
        }
        if (digits.length === 0) return '';
        if (digits.length <= 3) {
            return `(${digits}`;
        }
        if (digits.length <= 6) {
            return `(${digits.slice(0, 3)}) ${digits.slice(3)}`;
        }
        return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6, 10)}`;
    }

    function applyPhoneFormatting(input) {
        if (!input) return;
        const oldVal = input.value;
        const formatted = formatCanadianPhone(oldVal);
        if (oldVal !== formatted) {
            input.value = formatted;
        }
    }

    const isPhoneInput = (target) => target && (
        target.classList.contains('js-phone-input') ||
        ['phone', 'alternate_phone', 'emergency_phone'].includes(target.name)
    );

    document.addEventListener('input', (e) => {
        if (isPhoneInput(e.target)) {
            applyPhoneFormatting(e.target);
        }
    }, true);

    document.addEventListener('paste', (e) => {
        if (isPhoneInput(e.target)) {
            setTimeout(() => applyPhoneFormatting(e.target), 0);
        }
    }, true);

    document.addEventListener('blur', (e) => {
        if (isPhoneInput(e.target)) {
            applyPhoneFormatting(e.target);
        }
    }, true);

    document.querySelectorAll('.js-phone-input, input[name="phone"], input[name="alternate_phone"], input[name="emergency_phone"]').forEach(applyPhoneFormatting);

    document.addEventListener('shown.bs.modal', (e) => {
        if (e.target) {
            e.target.querySelectorAll('.js-phone-input, input[name="phone"], input[name="alternate_phone"], input[name="emergency_phone"]').forEach(applyPhoneFormatting);
        }
    });

    document.addEventListener('shown.bs.modal', (e) => {
        if (e.target) {
            e.target.querySelectorAll('form').forEach(bindFormSubmit);
        }
    });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initApp);
    } else {
        initApp();
    }
})();
