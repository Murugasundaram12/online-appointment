@php
    $staffUser = auth('staff')->user();
    $initials = $staffUser ? collect(explode(' ', $staffUser->name))->filter()->map(fn($part) => substr($part, 0, 1))->take(2)->implode('') : 'OA';
    $groups = [
        'Overview' => [
            ['Dashboard', 'dashboard', 'dashboard*', 'bx-grid-alt'],
            ['Calendar', 'calendar.index', 'calendar.*', 'bx-calendar'],
        ],
        'Management' => [
            ['Clients', 'clients.index', 'clients.*', 'bx-user-circle'],
            ['Staff', 'staff.index', 'staff.*', 'bx-group'],
            ['Schedule', 'schedule.index', 'schedule.*', 'bx-time-five'],
            ['Services', 'services.index', 'services.*', 'bx-layer-plus'],
            ['Categories', 'categories.index', 'categories.*', 'bx-category'],
            ['Insurance Companies', 'insurance-companies.index', 'insurance-companies.*', 'bx-shield-quarter'],
            ['Packages', 'packages.index', 'packages.*', 'bx-package'],
            ['Locations', 'locations.index', 'locations.*', 'bx-map'],
        ],
        'Finance' => [
            ['Invoices', 'invoices.index', 'invoices.*', 'bx-receipt'],
            ['Payments', 'payment-records.index', 'payment-records.*', 'bx-credit-card'],
            ['Payroll', 'payroll.index', 'payroll.*', 'bx-wallet'],
            ['Reports', 'reports.index', 'reports.*', 'bx-bar-chart-alt-2'],
        ],
        'Tools' => [
            ['Forms', 'forms.index', 'forms.*', 'bx-file'],
            ['Form Records', 'form-records.index', 'form-records.*', 'bx-list-check'],
        ],
        'Configuration' => [
            ['Business Settings', 'business-settings.index', 'business-settings.*', 'bx-cog'],
            ['Subscription', 'subscription.index', 'subscription.*', 'bx-crown'],
        ],
    ];
@endphp

<aside class="sidebar" id="sidebar-wrapper">
    <div class="sidebar-brand">
        <div class="brand-mark"><i class='bx bx-calendar-star'></i></div>
        <div>
            <div class="brand-title">{{ \App\Models\BusinessSetting::where('key', 'business_name')->value('value') ?: 'Online Appointment' }}</div>
            <div class="brand-subtitle">Business Management</div>
        </div>
    </div>

    @auth('staff')
        <div class="sidebar-profile">
            <div class="sidebar-avatar">{{ $initials }}</div>
            <div class="min-w-0">
                <div class="name text-truncate">{{ $staffUser->name }}</div>
                <div class="role text-truncate">{{ ucfirst(str_replace('_', ' ', $staffUser->access_level ?? 'staff')) }}</div>
            </div>
        </div>
    @endauth

    @foreach($groups as $group => $items)
        @php
            $visibleItems = collect($items)->filter(function ($item) use ($staffUser) {
                $route = $item[1];
                if (in_array($route, ['staff.index', 'payroll.index', 'reports.index', 'business-settings.index', 'subscription.index'], true)) {
                    return $staffUser && in_array($staffUser->access_level, ['admin', 'business_owner'], true);
                }
                return true;
            });
        @endphp

        @if($visibleItems->isNotEmpty())
            <div class="sidebar-section">
                <div class="sidebar-section-title">{{ $group }}</div>
                <nav class="sidebar-nav" aria-label="{{ $group }}">
                    @foreach($visibleItems as [$label, $route, $pattern, $icon])
                        <a href="{{ route($route) }}" class="sidebar-link {{ request()->routeIs($pattern) ? 'active' : '' }}">
                            <i class='bx {{ $icon }}'></i>
                            <span>{{ $label }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>
        @endif
    @endforeach

    @auth('staff')
        <div class="sidebar-section mt-auto">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-link border-0 w-100 text-start" style="background:transparent">
                    <i class='bx bx-log-out'></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    @endauth
</aside>
