<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clinic Portal | Welcome</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <!-- Inline styles for custom premium aesthetics -->
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --background: #0f172a; /* Slate 900 */
            --surface: #1e293b; /* Slate 800 */
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --border: rgba(255, 255, 255, 0.06);
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: 
                radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent 45rem),
                radial-gradient(circle at bottom left, rgba(6, 182, 212, 0.12), transparent 40rem),
                var(--background);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .portal-container {
            max-width: 900px;
            width: 100%;
            padding: 24px;
        }

        .portal-header {
            text-align: center;
            margin-bottom: 48px;
        }

        .portal-logo {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary), #06b6d4);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.75rem;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
            margin-bottom: 16px;
        }

        .portal-title {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
            background: linear-gradient(to right, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .portal-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            max-width: 500px;
            margin: 0 auto;
        }

        .portal-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px 32px;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .portal-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border-color: rgba(99, 102, 241, 0.4);
        }

        .portal-card-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 24px;
            transition: transform 0.22s ease;
        }

        .portal-card:hover .portal-card-icon {
            transform: scale(1.05);
        }

        .icon-patient {
            background: rgba(99, 102, 241, 0.1);
            color: #818cf8;
        }

        .icon-staff {
            background: rgba(6, 182, 212, 0.1);
            color: #22d3ee;
        }

        .portal-card-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 12px;
            letter-spacing: -0.01em;
        }

        .portal-card-desc {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 32px;
            flex-grow: 1;
        }

        .portal-btn {
            font-weight: 600;
            font-size: 0.85rem;
            padding: 10px 24px;
            border-radius: 10px;
            transition: all 0.2s ease;
            width: 100%;
        }

        .btn-patient {
            background: var(--primary);
            border: 1px solid var(--primary);
            color: white;
        }

        .portal-card:hover .btn-patient {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        .btn-staff {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }

        .portal-card:hover .btn-staff {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .portal-footer {
            margin-top: 56px;
            text-align: center;
            font-size: 0.8rem;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="portal-container">
        <header class="portal-header">
            <div class="portal-logo">
                <i class='bx bx-clinic'></i>
            </div>
            <h1 class="portal-title">
                {{ \App\Models\BusinessSetting::where('key', 'business_name')->value('value') ?: 'Clinic Portal' }}
            </h1>
            <p class="portal-subtitle">
                Welcome to our online medical clinic management and appointment booking hub. Please select a portal to proceed.
            </p>
        </header>

        <div class="row g-4 justify-content-center">
            <!-- Patient Portal Card -->
            <div class="col-md-5">
                <a href="{{ route('online-booking.index') }}" class="portal-card">
                    <div class="portal-card-icon icon-patient">
                        <i class='bx bx-calendar-check'></i>
                    </div>
                    <h2 class="portal-card-title">Patient Booking</h2>
                    <p class="portal-card-desc">
                        Schedule new appointments, view open calendars, select clinic locations, and book services instantly.
                    </p>
                    <span class="btn portal-btn btn-patient">Book Appointment</span>
                </a>
            </div>

            <!-- Staff Portal Card -->
            <div class="col-md-5">
                <a href="{{ route('login') }}" class="portal-card">
                    <div class="portal-card-icon icon-staff">
                        <i class='bx bx-id-card'></i>
                    </div>
                    <h2 class="portal-card-title">Staff Portal</h2>
                    <p class="portal-card-desc">
                        Access clinic administration, payroll reports, calendars, locations configuration, settings, and subscriber details.
                    </p>
                    <span class="btn portal-btn btn-staff">Staff Sign In</span>
                </a>
            </div>
        </div>

        <footer class="portal-footer">
            Powered by Online Appointment Business Management System &copy; {{ date('Y') }}
        </footer>
    </div>
</body>
</html>
