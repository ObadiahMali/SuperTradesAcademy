{{-- resources/views/layouts/navigation.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>@yield('title', 'SuperTrades Academy')</title>

  {{-- Inline, scoped styles for this layout (move to app.css later if desired) --}}
  <style>
    :root{
      --z-navbar:1050;
      --z-sidebar:1040;
      --z-overlay:1035;
      --sidebar-width:260px;
      --sidebar-collapsed-width:72px;
      --brand-color:#0f172a;
      --accent:#2563eb;
      --muted:#9aa6c0;
    }

    /* Reset small default */
    html,body{height:100%;margin:0;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;color:#0f172a;background:#fff;}

    /* Navbar */
    .site-navbar{
      position:fixed;inset:0 auto auto 0;height:64px;left:0;right:0;
      display:flex;align-items:center;justify-content:space-between;
      padding:0 1rem;background:#ffffff;border-bottom:1px solid rgba(15,23,42,0.06);
      box-shadow:0 6px 18px rgba(2,6,23,0.04);z-index:var(--z-navbar);
    }
    .site-navbar .left { display:flex; align-items:center; gap:0.6rem; }
    .site-navbar .brand { display:flex; align-items:center; gap:0.6rem; text-decoration:none; color:var(--brand-color); font-weight:700; }
    .site-navbar .brand img { height:34px; width:auto; border-radius:6px; box-shadow:0 2px 6px rgba(2,6,23,0.04); }
    .site-navbar .sidebar-toggle {
      display:inline-flex;align-items:center;justify-content:center;
      width:44px;height:44px;border-radius:8px;border:1px solid rgba(15,23,42,0.06);
      background:transparent;color:#374151;cursor:pointer;
    }
    .site-navbar .sidebar-toggle:focus{outline:2px solid rgba(37,99,235,0.18);outline-offset:2px;}
    .site-navbar .user-area { display:flex; align-items:center; gap:0.6rem; }

    .avatar {
      width:36px;height:36px;border-radius:999px;display:inline-block;text-align:center;line-height:36px;
      background:#f3f4f6;color:#374151;font-weight:700;font-size:14px;border:1px solid rgba(15,23,42,0.06);object-fit:cover;
    }

    /* Sidebar footer styles */
.sidebar-footer {
  background: rgb(13, 110, 253);   /* dark background */
  padding: 16px;
  border-top: 1px solid rgba(255,255,255,0.1);
  border-radius: 0 0 12px 12px;
  color: #fff;
}

.sidebar-footer h4 {
  font-size: 0.95rem;
  font-weight: 700;
  margin-bottom: 6px;
  color: #fff;
}

.sidebar-footer .plan-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: rgba(255,255,255,0.9);
}

.sidebar-footer .plan-detail {
  font-size: 0.8rem;
  color: rgba(255,255,255,0.75);
  margin-bottom: 8px;
}

.sidebar-footer .signals {
  margin-top: 10px;
}

.sidebar-footer .signals .plan-detail {
  font-weight: 600;
  color: #ffd43b; /* highlight price in gold */
}

    /* Sidebar (docked on desktop, overlay on mobile) */
    #sidebar {
      position:fixed;top:64px;left:0;height:calc(100vh - 64px);width:var(--sidebar-width);
      background:#f8f9fa;border-right:1px solid rgba(15,23,42,0.06);box-shadow:2px 0 8px rgba(2,6,23,0.04);
      padding:1rem;box-sizing:border-box;z-index:var(--z-sidebar);
      transform:translateX(-100%);transition:transform .28s ease,left .28s ease;
      overflow:auto;
    }
    /* Desktop: keep visible by default */
    @media (min-width: 992px){
      #sidebar{transform:none;left:0;}
      main.with-sidebar{margin-left:var(--sidebar-width);transition:margin-left .28s ease;}
    }

    /* When active on mobile */
    #sidebar.active{transform:translateX(0);left:0;}

    /* Overlay for mobile */
    .sidebar-overlay{
      position:fixed;inset:64px 0 0 0;background:rgba(0,0,0,0.35);z-index:var(--z-overlay);display:none;
    }
    .sidebar-overlay.visible{display:block;}

    /* Nav links */
    #sidebar .nav{display:flex;flex-direction:column;gap:6px;margin:0;padding:0;list-style:none;}
    #sidebar .nav-link{
      display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;color:#374151;text-decoration:none;font-weight:600;
    }
    #sidebar .nav-link:hover{background:rgba(37,99,235,0.06);color:var(--brand-color);}
    #sidebar .nav-link.active{background:linear-gradient(90deg, rgba(37,99,235,0.12), rgba(37,99,235,0.06));box-shadow:inset 4px 0 0 var(--accent);color:#0f172a;}

    /* Quick actions and footer */
    .sidebar-section{margin-top:1rem;}
    .sidebar-footer{margin-top:auto;padding-top:1rem;border-top:1px solid rgba(15,23,42,0.04);color:var(--muted);font-size:13px;}

    /* Main content */
    main{padding:80px 24px 24px 24px;min-height:100vh;box-sizing:border-box;transition:margin-left .28s ease;}

    /* Responsive tweaks */
    @media (max-width:991px){
      .site-navbar .brand span{display:inline-block;font-size:15px;}
      main{padding:80px 16px 16px 16px;}
    }

    /* Utility: hide broken images gracefully */
    img[onerror-hide]{display:inline-block;}
  </style>
</head>
<body>
  {{-- Navbar --}}
  <header class="site-navbar" role="banner">
    <div class="left">
      {{-- Sidebar toggle controls the sidebar (accessible) --}}
      <button id="sidebarToggle" class="sidebar-toggle" aria-controls="sidebar" aria-expanded="false" aria-label="Open navigation">
        <span class="navbar-toggler-icon" aria-hidden="true" style="display:inline-block;width:18px;height:14px;background:linear-gradient(#374151 0 0);mask:linear-gradient(#000,#000);"></span>
      </button>

      <a class="brand" href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}">
        <img src="{{ asset('images/logo.png') }}" alt="SuperTrades Academy logo" onerror="this.style.display='none'">
        <span>SuperTrades Academy</span>
      </a>
    </div>

    <div class="user-area">
      {{-- Optional small nav links (kept minimal) --}}
      <nav aria-label="Top navigation" style="display:flex;align-items:center;gap:8px;"></nav>

      {{-- User dropdown (Bootstrap's JS expected) --}}
      <ul class="navbar-nav" style="list-style:none;margin:0;padding:0;display:flex;align-items:center;">
        <li class="nav-item dropdown" style="position:relative;">
          <a id="userDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:inherit;">
            @php $user = Auth::user(); @endphp
            @if(!empty($user->profile_photo_path))
              <img src="{{ asset($user->profile_photo_path) }}" alt="{{ $user->name }} avatar" class="avatar" onerror="this.style.display='none'">
            @else
              <span class="avatar">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
            @endif
            <span class="d-none d-lg-inline">{{ $user->name ?? '' }}</span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown" style="min-width:200px;border-radius:10px;padding:6px;border:1px solid rgba(15,23,42,0.06);box-shadow:0 14px 40px rgba(2,6,23,0.12);">
            @if(Route::has('profile.edit'))
              <li><a class="dropdown-item" href="{{ route('profile.edit') }}" style="display:block;padding:10px 12px;border-radius:8px;font-weight:600;color:#0f172a;text-decoration:none;">Profile</a></li>
            @endif
            <li><div style="height:1px;background:rgba(15,23,42,0.04);margin:6px 0;border-radius:2px;"></div></li>
            <li>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item" style="display:block;padding:10px 12px;border-radius:8px;font-weight:600;color:#0f172a;background:transparent;border:0;width:100%;text-align:left;">Log Out</button>
              </form>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </header>

  {{-- Sidebar overlay (for mobile) --}}
  <div id="sidebarOverlay" class="sidebar-overlay" aria-hidden="true"></div>

  {{-- Sidebar (navigation) --}}
  <aside id="sidebar" role="navigation" aria-label="Primary" tabindex="-1">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
      <a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}" class="brand" style="text-decoration:none;color:inherit;">
        {{-- <img src="{{ asset('images/logo.png') }}" alt="Logo" onerror="this.style.display='none'"> --}}
        <span style="font-weight:700;color:var(--brand-color);">SuperTrades Academy</span>
      </a>
    </div>
<nav class="nav flex-column" aria-label="Main">
    {{-- Secretary dashboard --}}
    @role('secretary')
        @if(Route::has('secretary.dashboard'))
            <a href="{{ route('secretary.dashboard') }}"
               class="nav-link {{ request()->routeIs('secretary.dashboard') ? 'active' : '' }}">
                Dashboard
            </a>
        @endif
    @endrole

    {{-- Administrator dashboard --}}
    @role('administrator')
        @if(Route::has('admin.dashboard'))
            <a href="{{ route('admin.dashboard') }}"
               class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                Dashboard
            </a>
        @endif
    @endrole

    {{-- Shared secretary menu (visible to secretary AND administrator) --}}
    @hasanyrole('secretary|administrator')
        @if(Route::has('secretary.students.index'))
            <a href="{{ route('secretary.students.index') }}"
               class="nav-link {{ request()->routeIs('secretary.students.*') ? 'active' : '' }}">
                Students
            </a>
        @endif

        @if(Route::has('secretary.intakes.index'))
            <a href="{{ route('secretary.intakes.index') }}"
               class="nav-link {{ request()->routeIs('secretary.intakes.*') ? 'active' : '' }}">
                Intakes
            </a>
        @endif

        @if(Route::has('secretary.payments.index'))
            <a href="{{ route('secretary.payments.index') }}"
               class="nav-link {{ request()->routeIs('secretary.payments.*') ? 'active' : '' }}">
                Payments
            </a>
        @endif

        @if(Route::has('secretary.expenses.index'))
            <a href="{{ route('secretary.expenses.index') }}"
               class="nav-link {{ request()->routeIs('secretary.expenses.*') ? 'active' : '' }}">
                Expenses
            </a>
        @endif
    @endhasanyrole

    {{-- Administrator-only extras --}}
    @role('administrator')
        @if(Route::has('admin.users.index'))
            <a href="{{ route('admin.users.index') }}"
               class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                Users
            </a>
        @endif

        @if(Route::has('admin.employees.index'))
            <a href="{{ route('admin.employees.index') }}"
               class="nav-link {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}">
                Employees
            </a>
        @endif

        @if(Route::has('admin.reports.index'))
            <a href="{{ route('admin.reports.index') }}"
               class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                Reports
            </a>
        @endif

        {{-- Plans (admin only) --}}
        @if(Route::has('admin.plans.index'))
            <a href="{{ route('admin.plans.index') }}"
               class="nav-link {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
                Plans
            </a>
        @endif
    @endrole
</nav>

    <div class="sidebar-section" aria-hidden="false">
      <div style="font-size:12px;color:var(--muted);font-weight:700;margin-top:12px;">Quick Actions</div>
      <div style="display:grid;gap:8px;margin-top:8px;">
        @if(Route::has('secretary.students.create'))
          <a href="{{ route('secretary.students.create', App\Models\Intake::where('active',true)->first()?->id ?? 0) }}" class="btn btn-primary btn-sm" style="display:inline-block;padding:8px 10px;border-radius:6px;text-decoration:none;color:#fff;background:var(--accent);border:0;">Register Student</a>
        @endif
        @if(Route::has('secretary.payments.index'))
          <a href="{{ route('secretary.payments.index') }}" class="btn btn-outline-secondary btn-sm" style="display:inline-block;padding:8px 10px;border-radius:6px;text-decoration:none;color:#374151;border:1px solid rgba(15,23,42,0.06);">Payments</a>
        @endif
      </div>
    </div>

    <div class="sidebar-footer" aria-hidden="false">
      <div style="font-weight:700;color:#fff;margin-bottom:6px;">Welcome back </div>
      <div style="color:#fff;font-weight:700;">{{ $user->name ?? 'User' }}</div>
      <div style="margin-top:10px;color:var(--muted);font-size:13px;">
        <div class="fw-semibold" style="color:#fff;">Physical Mentorship</div>
        <div style="color:rgba(255,255,255,0.8);">$150 · Akamwesi Mall</div>
        <div style="margin-top:8px;color:#fff;font-weight:700;">Signals Plans</div>
        <div style="color:rgba(255,255,255,0.8);">1m $59 · 3m $79 · 6m $99 · 12m $150</div>
      </div>
    </div>
  </aside>

  {{-- Main content area --}}
  <main id="mainContent" role="main" class="@if(request()->is('/')) @endif">
    @yield('content')
  </main>

  {{-- Scripts: keep minimal and robust. Move to app.js in production. --}}
  <script>
    (function () {
      const toggle = document.getElementById('sidebarToggle');
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('sidebarOverlay');
      const main = document.getElementById('mainContent');

      // Utility: detect desktop breakpoint
      function isDesktop() { return window.matchMedia('(min-width: 992px)').matches; }

      // Open/close functions with accessibility
      function openSidebar() {
        sidebar.classList.add('active');
        overlay.classList.add('visible');
        overlay.removeAttribute('hidden');
        toggle.setAttribute('aria-expanded', 'true');
        // prevent background scroll on mobile overlay
        if (!isDesktop()) document.body.style.overflow = 'hidden';
        // shift main content on desktop
        if (isDesktop()) main.classList.add('with-sidebar');
        // focus the sidebar for keyboard users
        sidebar.setAttribute('tabindex', '-1');
        sidebar.focus();
      }

      function closeSidebar() {
        sidebar.classList.remove('active');
        overlay.classList.remove('visible');
        overlay.setAttribute('hidden', 'true');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        if (isDesktop()) main.classList.remove('with-sidebar');
        toggle.focus();
      }

      // Toggle handler
      toggle.addEventListener('click', function (e) {
        e.preventDefault();
        if (sidebar.classList.contains('active')) closeSidebar(); else openSidebar();
      });

      // Overlay click closes
      overlay.addEventListener('click', closeSidebar);

      // Close on Escape
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) closeSidebar();
      });

      // Close overlay when a nav link is clicked (mobile)
      document.querySelectorAll('#sidebar .nav-link').forEach(function (el) {
        el.addEventListener('click', function () {
          if (!isDesktop()) closeSidebar();
        });
      });

      // Ensure correct initial state on resize
      window.addEventListener('resize', function () {
        if (isDesktop()) {
          // ensure sidebar visible and overlay hidden on desktop
          sidebar.classList.add('active');
          overlay.classList.remove('visible');
          overlay.setAttribute('hidden', 'true');
          document.body.style.overflow = '';
          main.classList.add('with-sidebar');
          toggle.setAttribute('aria-expanded', 'true');
        } else {
          // mobile: hide sidebar by default
          sidebar.classList.remove('active');
          overlay.classList.remove('visible');
          overlay.setAttribute('hidden', 'true');
          main.classList.remove('with-sidebar');
          toggle.setAttribute('aria-expanded', 'false');
        }
      });

      // Initialize state
      if (isDesktop()) {
        sidebar.classList.add('active');
        main.classList.add('with-sidebar');
        overlay.setAttribute('hidden', 'true');
        toggle.setAttribute('aria-expanded', 'true');
      } else {
        sidebar.classList.remove('active');
        main.classList.remove('with-sidebar');
        overlay.setAttribute('hidden', 'true');
        toggle.setAttribute('aria-expanded', 'false');
      }
    })();
  </script>
</body>
</html>