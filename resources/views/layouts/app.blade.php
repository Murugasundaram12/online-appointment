<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Management System')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
    @stack('styles')
</head>

<body class="bg-light">
    <div class="d-flex app-shell" id="wrapper">
        <!-- Sidebar -->
        @include('partials.sidebar')

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <header class="app-topbar">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-white d-lg-none" type="button" data-sidebar-toggle aria-label="Open navigation">
                        <i class='bx bx-menu fs-4'></i>
                    </button>
                    <div>
                        <h1 class="topbar-title">@yield('title', 'Dashboard')</h1>
                        <div class="breadcrumb-mini">Home / @yield('title', 'Dashboard')</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3 flex-grow-1 justify-content-end">
                    <form class="topbar-search" method="GET" action="{{ route('clients.index') }}" role="search">
                        <i class='bx bx-search'></i>
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search clients, invoices, services" aria-label="Search">
                    </form>
                    @auth('staff')
                        <div class="dropdown">
                            <button class="btn btn-white d-flex align-items-center gap-2" data-bs-toggle="dropdown" type="button" aria-expanded="false">
                                <span class="avatar-initials" style="width:30px;height:30px;font-size:.75rem">{{ substr(auth('staff')->user()->name, 0, 2) }}</span>
                                <span class="d-none d-md-inline">{{ auth('staff')->user()->name }}</span>
                                <i class='bx bx-chevron-down'></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                <div class="px-3 py-2 small text-muted">{{ auth('staff')->user()->email }}</div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item" type="submit">Logout</button>
                                </form>
                            </div>
                        </div>
                    @endauth
                </div>
            </header>
            <main class="pb-5">
                @include('partials.alerts')
                @yield('content')
            </main>
        </div>
    </div>
    @include('partials.global-modals')
    <!-- Bootstrap and JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/script.js') }}?v={{ filemtime(public_path('js/script.js')) }}"></script>
    @stack('scripts')
</body>

</html>
