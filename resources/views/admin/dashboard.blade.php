{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<style>
  /* Card tweaks */
  .dashboard-card { border-radius:12px; padding:18px; }
  .metric-title { font-size:0.9rem; color:#64748b; font-weight:600; letter-spacing:0.2px; display:flex; align-items:center; gap:8px; }
  .metric-value { font-size:1.5rem; font-weight:700; color:#071033; margin-top:6px; }
  .metric-sub { font-size:0.875rem; color:#475569; }

  /* Icon sizing */
  .icon-sm { width:18px; height:18px; display:inline-block; vertical-align:middle; }
  .icon-md { width:22px; height:22px; display:inline-block; vertical-align:middle; }
  .icon-lg { width:28px; height:28px; display:inline-block; vertical-align:middle; }

  /* Payments card layout */
  .payments-card-content { display:block; width:100%; box-sizing:border-box; }
  .card-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:12px; }
  .card-actions .btn { font-size:0.82rem; padding:6px 8px; }

  /* Totals row */
  .payments-main { display:flex; flex-direction:column; gap:12px; }
  .payments-totals { display:flex; gap:18px; align-items:flex-start; flex-wrap:wrap; }
  .payments-total { min-width:180px; flex:1 1 220px; display:flex; flex-direction:column; gap:6px; }
  .payments-currency-badge { display:inline-block; background:#eef2ff; color:#0b6ef6; font-weight:700; padding:6px 8px; border-radius:8px; font-size:0.85rem; }
  .payments-number { font-size:1.25rem; font-weight:800; color:#071033; margin-top:4px; white-space:nowrap; text-overflow:ellipsis; overflow:hidden; }

  /* Expected grid - two-columns then progress row */
  .expected-grid { display:grid; grid-template-columns: 1fr 1fr; gap:12px 18px; align-items:start; }
  .expected-item { min-width:0; }
  .expected-label { font-size:0.85rem; color:#475569; font-weight:600; }
  .expected-value { font-size:1rem; color:#0b6ef6; font-weight:800; margin-top:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .outstanding-value { color:#c2410c; font-weight:800; font-size:1rem; margin-top:4px; }

  .expected-progress { grid-column: 1 / -1; margin-top:6px; }
  .progress-track { width:100%; height:12px; background:#eef2ff; border-radius:999px; overflow:hidden; }
  .progress-fill { height:100%; background:linear-gradient(90deg,#1680ff,#0b6ef6); transition:width 600ms ease; }

  @media (max-width: 768px) {
    .payments-totals { gap:12px; }
    .expected-grid { grid-template-columns: 1fr; }
    .payments-number { font-size:1.05rem; }
  }

  .payments-amount { font-weight:800; color:#083344; font-size:1.1rem; }
  .payments-currency { font-weight:700; color:#0b6ef6; }

  .expected-box { background:linear-gradient(90deg,#f1f9ff,#ffffff); border-radius:10px; padding:10px; margin-top:10px; border:1px solid rgba(11,110,246,0.06); }
  .progress-small { height:10px; border-radius:8px; overflow:hidden; background:#eef2ff; }
  .progress-small > .bar { height:100%; background:linear-gradient(90deg,#1680ff,#0b6ef6); }

  .recent-name { font-weight:700; color:#0b2540; }
  .recent-meta { font-size:0.85rem; color:#64748b; }
  .recent-amount { font-weight:800; color:#0b6ef6; }

  .intake-name { font-weight:700; color:#0b2540; }
  .intake-meta { color:#64748b; font-size:0.875rem; }

  .expense-amount { font-weight:800; color:#c2410c; }
  .expense-count { font-weight:700; color:#071033; }

  .muted-note { color:#94a3b8; font-size:0.85rem; }
  .badge-compact { font-size:0.78rem; padding:6px 8px; border-radius:8px; }
  .small-card { background:#fff; border-radius:12px; padding:14px; box-shadow:0 6px 18px rgba(10,25,47,0.04); }
</style>

<div class="container-fluid">
  <div class="page-header d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Admin Dashboard</h3>
      <div class="text-muted small">Overview of operations, intakes, payments, expenses, employees and reports</div>
    </div>
  </div>

  {{-- Top metrics row --}}
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card p-3">
        <div class="metric-title">
          {{-- intake icon --}}
          <svg class="icon-sm" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M12 2L3 7v6c0 5 3.8 9.7 9 11 5.2-1.3 9-6 9-11V7l-9-5z" stroke="#0b6ef6" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="12" cy="11" r="2.2" fill="#0b6ef6"/>
          </svg>
          Active Intakes
        </div>
        <div class="metric-value">{{ $activeIntakeCount ?? 0 }}</div>
        <div class="metric-sub">Currently active cohorts</div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card p-3">
        <div class="metric-title">
          {{-- students icon --}}
          <svg class="icon-sm" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M12 12c2.2 0 4-1.8 4-4s-1.8-4-4-4-4 1.8-4 4 1.8 4 4 4z" stroke="#0b6ef6" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M4 20c0-3.3 3.6-6 8-6s8 2.7 8 6" stroke="#0b6ef6" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Total Students
        </div>
        <div class="metric-value">{{ $studentCount ?? 0 }}</div>
        <div class="metric-sub">All enrolled students</div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card p-3">
        <div class="metric-title">
          {{-- expenses icon --}}
          <svg class="icon-sm" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M21 11.5V6a2 2 0 0 0-2-2h-4" stroke="#c2410c" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M3 12.5v5a2 2 0 0 0 2 2h4" stroke="#c2410c" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="12" cy="12" r="3.2" stroke="#c2410c" stroke-width="1.2" />
          </svg>
          Grand Total Expenses
        </div>
        <div class="metric-value text-danger">UGX {{ number_format($totalExpensesAllTime ?? 0, 2) }}</div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card p-3">
        <div class="metric-title">
          {{-- balance icon --}}
          <svg class="icon-sm" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M12 3v18" stroke="#16a34a" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M5 7h14" stroke="#16a34a" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M5 17h14" stroke="#16a34a" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Current Balance (This Month)
        </div>
        <div class="metric-value text-success">UGX {{ number_format($currentBalanceUGXMonth ?? 0, 2) }}</div>
        <div class="metric-sub">≈ USD {{ number_format($currentBalanceUSDMonth ?? 0, 2) }}</div>
        <div class="metric-sub mt-1">Receipts: UGX {{ number_format($totalReceiptsUGXMonth ?? 0, 2) }}</div>
      </div>
    </div>
  </div>

  {{-- All-time totals row --}}
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <div class="card p-3">
        <div class="metric-title">
          {{-- collected icon --}}
          <svg class="icon-md" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M12 1v22" stroke="#0b6ef6" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M5 7h14" stroke="#0b6ef6" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M7 17h10" stroke="#0b6ef6" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Total Collected (All time)
        </div>
        <div class="metric-value">UGX {{ number_format($totalCollectedUGXAll ?? ($totalReceiptsUGXAll ?? 0), 2) }}</div>
        <div class="metric-sub">All receipts converted to UGX</div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card p-3">
        <div class="metric-title">
          {{-- net balance icon --}}
          <svg class="icon-md" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <rect x="3" y="6" width="18" height="12" rx="2" stroke="#16a34a" stroke-width="1.4"/>
            <path d="M8 10h8" stroke="#16a34a" stroke-width="1.4" stroke-linecap="round"/>
            <path d="M8 14h5" stroke="#16a34a" stroke-width="1.4" stroke-linecap="round"/>
          </svg>
          Total Balance (All time)
        </div>
        <div class="metric-value text-success">UGX {{ number_format($currentBalanceUGXAll ?? 0, 2) }}</div>
        <div class="metric-sub">≈ USD {{ number_format($currentBalanceUSDAll ?? 0, 2) }}</div>
        <div class="metric-sub mt-1">Receipts minus expenses (all time)</div>
      </div>
    </div>
  </div>

  {{-- Payments summary --}}
  <div class="card p-3 mb-3">
    <div class="card-head">
      <div>
        <h6 class="mb-1">Payments This Month</h6>
        <div class="small text-muted">Period: {{ now()->format('F Y') }} · Last updated: {{ now()->format('d M Y H:i') }}</div>
      </div>
      <div class="card-actions">
        {{-- payments icon --}}
        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M21 12v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6" stroke="#0b6ef6" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M7 10l5 4 5-4" stroke="#0b6ef6" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
    </div>

    <div class="row mt-2">
      <div class="col-sm-6">
        <div class="border rounded p-3">
          <div class="small text-muted">UGX</div>
          <div class="h4">{{ number_format($ugxThisMonth ?? 0, 2) }}</div>
          <div class="small text-muted">Local receipts</div>
        </div>
      </div>

      <div class="col-sm-6">
        <div class="border rounded p-3">
          <div class="small text-muted">USD</div>
          <div class="h4">{{ number_format($usdThisMonth ?? 0, 2) }}</div>
          <div class="small text-muted">Foreign receipts</div>
        </div>
      </div>
    </div>

    <div class="expected-box">
      <div class="expected-label">Expected UGX</div>
      <div class="expected-value">UGX {{ number_format($expectedUGX ?? 0, 2) }}</div>

      <div class="expected-label mt-2">Outstanding</div>
      <div class="outstanding-value">UGX {{ number_format($outstandingUGX ?? 0, 2) }}</div>

      <div class="progress-small mt-2" aria-hidden="false">
        <div class="bar" style="width: {{ $collectedPct ?? 0 }}%;"></div>
      </div>

      <div class="muted-note mt-1">
        {{ $expectedUGX > 0 ? ($collectedPct ?? 0) . '% collected of expected UGX' : 'No expected target set' }}
      </div>
    </div>
  </div>

  {{-- Recent Payments --}}
  <div class="card p-3 mb-3">
    <h6 class="mb-2">
      {{-- recent icon --}}
      <svg class="icon-sm" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M12 6v6l4 2" stroke="#0b6ef6" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="12" cy="12" r="9" stroke="#0b6ef6" stroke-width="1.2"/>
      </svg>
      Recent Payments
    </h6>

    <div class="list-group list-group-flush">
      @forelse($recentPayments ?? [] as $pay)
        <div class="list-group-item d-flex justify-content-between align-items-start">
          <div>
           <div class="recent-name">{{ $pay->student->first_name }} {{ $pay->student->last_name }}</div>
            <div class="recent-meta">
              {{ $pay->method ?? 'N/A' }} · {{ optional($pay->created_at)->format('d M Y H:i') ?? 'No date' }}
            </div>
          </div>

          <div class="text-end">
            <div class="recent-amount">
              {{ $pay->currency ?? 'UGX' }} {{ number_format($pay->amount ?? 0, 2) }}
            </div>
          </div>
        </div>
      @empty
        <div class="list-group-item text-muted">No recent payments</div>
      @endforelse
    </div>
  </div>

  {{-- Active Intakes overview --}}
<div class="card p-3 mb-3">
  <h6 class="mb-2">
    {{-- intakes icon --}}
    <svg class="icon-sm" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <path d="M3 7h18" stroke="#0b6ef6" stroke-width="1.2" stroke-linecap="round"/>
      <path d="M3 12h18" stroke="#0b6ef6" stroke-width="1.2" stroke-linecap="round"/>
      <path d="M3 17h18" stroke="#0b6ef6" stroke-width="1.2" stroke-linecap="round"/>
    </svg>
    Active Intakes
  </h6>

  <div class="row">
    @forelse($activeIntakes ?? [] as $intake)
      <div class="col-md-4 mb-2">
        <div class="border rounded p-2">
          <div class="intake-name">{{ $intake->name ?? 'Untitled intake' }}</div>

          <div class="intake-meta">
            {{ optional($intake->start_date)->format('d M Y') ?? 'No start date' }}
          </div>

         <div class="small">Students: {{ $intake->students_count ?? 0 }}</div>
          {{-- <div class="small">Outstanding: UGX {{ number_format($intake->outstanding ?? 0, 2) }}</div>
          <div class="small">Paid: {{ $intake->paid_pct ?? 0 }}%</div> --}}
        </div>
      </div>
    @empty
      <div class="col-12 text-muted">No active intakes</div>
    @endforelse
  </div>
</div>

  {{-- Employees and Reports --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="card p-3">
        <div class="metric-title">
          {{-- employees icon --}}
          <svg class="icon-sm" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M16 11c1.7 0 3-1.3 3-3s-1.3-3-3-3-3 1.3-3 3 1.3 3 3 3z" stroke="#0b6ef6" stroke-width="1.2"/>
            <path d="M8 11c1.7 0 3-1.3 3-3S9.7 5 8 5 5 6.3 5 8s1.3 3 3 3z" stroke="#0b6ef6" stroke-width="1.2"/>
            <path d="M2 20c0-3 3.6-5 8-5s8 2 8 5" stroke="#0b6ef6" stroke-width="1.2"/>
          </svg>
          Employees
        </div>
        <div class="metric-value">{{ $employeesCount ?? 0 }}</div>
        <div class="metric-sub">Active staff</div>
        <a href="{{ route('admin.employees.index') }}" class="btn btn-sm btn-outline-primary mt-2">Manage</a>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card p-3">
        <div class="metric-title">
          {{-- reports icon --}}
          <svg class="icon-sm" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M4 7h16" stroke="#475569" stroke-width="1.2" stroke-linecap="round"/>
            <path d="M4 12h16" stroke="#475569" stroke-width="1.2" stroke-linecap="round"/>
            <path d="M4 17h10" stroke="#475569" stroke-width="1.2" stroke-linecap="round"/>
          </svg>
          Reports
        </div>
        <div class="metric-value">{{ $reportsCount ?? 0 }}</div>
        <div class="metric-sub">Generated reports</div>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary mt-2">Open</a>
      </div>
    </div>
  </div>
</div>
@endsection