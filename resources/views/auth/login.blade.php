<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}&fix=20260801">
    <style>
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
        }

        .login-shell {
            width: min(1060px, calc(100% - 32px));
            overflow: hidden;
            border-radius: 28px;
            box-shadow: 0 30px 90px rgba(15, 23, 42, .18);
            background: #fff;
        }

        .login-panel {
            background: linear-gradient(135deg, #111827, #3730a3);
            color: white;
            padding: 56px;
            min-height: 620px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .login-card {
            padding: 56px;
        }

        .trust-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 999px;
            padding: 8px 12px;
            color: #cbd5e1;
            font-size: .85rem;
        }

        .feature-line {
            display: flex;
            gap: 12px;
            align-items: center;
            color: #dbeafe;
            margin-top: 18px;
        }

        .feature-line i {
            color: #67e8f9;
        }

        @media (max-width: 900px) {
            .login-panel {
                min-height: auto;
                padding: 32px;
            }

            .login-card {
                padding: 32px;
            }
        }
    </style>
</head>

<body>
    <div class="login-shell">
        <div class="row g-0">
            <div class="col-lg-6 login-panel">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-5">
                        <div class="brand-mark"><i class='bx bx-calendar-star'></i></div>
                        <div>
                            <div class="fw-bold fs-5">
                                {{ config('app.name', 'Online Appointment') }}
                            </div>
                            {{-- <div class="text-white-50 small">Premium business operations suite</div> --}}
                        </div>
                    </div>
                    {{-- <span class="trust-pill"><i class='bx bx-lock-alt'></i> Secure staff workspace</span> --}}
                    <h1 class="display-6 fw-bold mt-4 mb-3">Welcome back</h1>
                    {{-- <p class="text-white-50 fs-6 mb-4">Sign in to manage appointments, clients, staff, payments, and
                        business operations.</p> --}}
                    {{-- <div class="feature-line"><i class='bx bx-check-shield'></i><span>Protected staff-only access</span>
                    </div>
                    <div class="feature-line"><i class='bx bx-calendar-check'></i><span>Calendar, schedule, and booking
                            tools</span></div>
                    <div class="feature-line"><i class='bx bx-credit-card'></i><span>Invoices, payments, and payroll in
                            one place</span></div> --}}
                </div>
                {{-- <div class="text-white-50 small">Built for salons, clinics, spas, therapy centers, and consultation
                    teams.</div> --}}
            </div>
            <div class="col-lg-6 login-card">
                <div class="mb-4">
                    <h2 class="fw-bold mb-1">Sign in</h2>
                    <p class="text-muted mb-0">Use your staff credentials to continue.</p>
                </div>
                <form method="POST" action="{{ route('login.store') }}" id="loginForm" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="email">Email <span class="required-mark">*</span></label>
                        @error('email')
                            <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                {{ $message }}
                            </div>
                        @enderror
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                            class="form-control form-control-lg @error('email') is-invalid @enderror" required autofocus autocomplete="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password <span class="required-mark">*</span></label>
                        @error('password')
                            <div class="invalid-feedback d-block app-field-error text-danger small mb-1 fw-medium" role="alert">
                                {{ $message }}
                            </div>
                        @enderror
                        <div class="input-group">
                            <input type="password" id="password" name="password" class="form-control form-control-lg @error('password') is-invalid @enderror"
                                required autocomplete="current-password">
                            <button class="btn btn-white js-toggle-password-btn" type="button" id="togglePassword"
                                data-target="#password" aria-label="Show password"><i class='bx bx-show'></i></button>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <span class="text-muted small">Password reset is handled by admin.</span>
                    </div>
                    <button class="btn btn-primary btn-lg w-100" type="submit" id="loginButton">Sign in
                        securely</button>
                </form>
            </div>
        </div>
    </div>
    @include('partials.alerts')
    <div class="toast-container app-toast-container position-fixed top-0 end-0 p-3" aria-live="polite" aria-atomic="true"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/script.js') }}?v={{ filemtime(public_path('js/script.js')) }}"></script>
    <script>
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            window.AppFormErrors?.attachAutoClear(loginForm);
            loginForm.addEventListener('submit', (e) => {
                window.AppFormErrors?.clear(loginForm);
                if (window.AppFormErrors && !window.AppFormErrors.validate(loginForm)) {
                    e.preventDefault();
                    return false;
                }
                const button = document.getElementById('loginButton');
                if (button) {
                    button.textContent = 'Signing in...';
                    button.disabled = true;
                }
            });
        }
    </script>
</body>

</html>
