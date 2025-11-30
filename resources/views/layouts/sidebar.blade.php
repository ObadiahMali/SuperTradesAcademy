{{-- resources/views/layouts/sidebar.blade.php --}}
<aside class="bg-light border-end vh-100 d-flex flex-column" style="width: 260px;">
    <div class="p-3 flex-grow-1 d-flex flex-column">
        {{-- Brand --}}
        <div class="mb-4 d-flex align-items-center">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" height="36" class="me-2">
                <span class="fs-6 fw-bold text-dark">SuperTrades Academy</span>
            </a>
        </div>

        {{-- Navigation --}}
        <nav class="nav flex-column mb-3">
            <a class="nav-link {{ request()->routeIs('secretary.dashboard') ? 'active' : '' }}"
               href="{{ route('secretary.dashboard') }}">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>

            <a class="nav-link {{ request()->routeIs('secretary.students.*') ? 'active' : '' }}"
               href="{{ route('secretary.students.index') }}">
                <i class="bi bi-people me-2"></i> Students
            </a>

            <a class="nav-link {{ request()->routeIs('secretary.intakes.*') ? 'active' : '' }}"
               href="{{ route('secretary.intakes.index') }}">
                <i class="bi bi-calendar3 me-2"></i> Intakes
            </a>

            <a class="nav-link {{ request()->routeIs('secretary.payments.*') ? 'active' : '' }}"
               href="{{ route('secretary.payments.index') }}">
                <i class="bi bi-cash-stack me-2"></i> Payments
            </a>

            <a class="nav-link {{ request()->routeIs('secretary.expenses.*') ? 'active' : '' }}"
               href="{{ route('secretary.expenses.index') }}">
                <i class="bi bi-receipt me-2"></i> Expenses
            </a>

            @role('admin')
                <a class="nav-link {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}"
                   href="{{ route('admin.employees.index') }}">
                    <i class="bi bi-person-badge me-2"></i> Employees
                </a>

                <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"
                   href="{{ route('admin.reports.index') }}">
                    <i class="bi bi-bar-chart-line me-2"></i> Reports
                </a>
            @endrole
        </nav>

        <hr class="my-3">

        {{-- Quick Actions --}}
        <div class="mb-3">
            <div class="text-muted small fw-semibold">Quick Actions</div>
            <div class="mt-2 d-grid gap-2">
                <a href="{{ route('secretary.students.create', App\Models\Intake::where('active',true)->first()?->id ?? 0) }}"
                   class="btn btn-primary btn-sm">
                    Register Student
                </a>
                <a href="{{ route('secretary.payments.index') }}" class="btn btn-outline-secondary btn-sm">
                    Payments
                </a>

                @role('admin')
                    <a href="{{ route('admin.employees.create') }}" class="btn btn-primary btn-sm">Add Employee</a>
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm">Reports</a>
                @endrole
            </div>
        </div>

        {{-- Plans & Contact --}}
        <div class="mt-auto">
            <div class="text-muted small fw-semibold">Plans & Contact</div>
            <div class="mt-2 small">
                <div class="fw-semibold">Physical Mentorship</div>
                <div class="text-muted">$150 · Akamwesi Mall</div>

                <div class="mt-2 fw-semibold">Signals Plans</div>
                <div class="text-muted">1m $59 · 3m $79 · 6m $99 · 12m $150</div>
            </div>
        </div>
    </div>
</aside>