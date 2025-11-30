@extends('layouts.app')

@section('content')

<style>
  /* Card tweaks */
  .dashboard-card { border-radius:12px; padding:18px; }
  .metric-title { font-size:0.9rem; color:#64748b; font-weight:600; letter-spacing:0.2px; }
  .metric-value { font-size:1.5rem; font-weight:700; color:#071033; margin-top:6px; }
  .metric-sub { font-size:0.875rem; color:#475569; }

  /* Payments box */

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
.expected-item { min-width:0; } /* allow ellipsis on small screens */
.expected-label { font-size:0.85rem; color:#475569; font-weight:600; }
.expected-value { font-size:1rem; color:#0b6ef6; font-weight:800; margin-top:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.outstanding-value { color:#c2410c; font-weight:800; font-size:1rem; margin-top:4px; }

/* Progress occupies full width under the two columns */
.expected-progress { grid-column: 1 / -1; margin-top:6px; }
.progress-track { width:100%; height:12px; background:#eef2ff; border-radius:999px; overflow:hidden; }
.progress-fill { height:100%; background:linear-gradient(90deg,#1680ff,#0b6ef6); transition:width 600ms ease; }

/* Responsive adjustments */
@media (max-width: 768px) {
  .payments-totals { gap:12px; }
  .expected-grid { grid-template-columns: 1fr; }
  .payments-number { font-size:1.05rem; }
}
  .payments-amount { font-weight:800; color:#083344; font-size:1.1rem; }
  .payments-currency { font-weight:700; color:#0b6ef6; }

  /* Expected / Outstanding */
  .expected-box { background:linear-gradient(90deg,#f1f9ff,#ffffff); border-radius:10px; padding:10px; margin-top:10px; border:1px solid rgba(11,110,246,0.06); }
  .expected-label { font-size:0.85rem; color:#475569; }
  .expected-value { font-size:1rem; font-weight:700; color:#0b6ef6; }

  .outstanding-value { color:#c2410c; font-weight:800; font-size:1rem; }

  /* Progress / percentage */
  .progress-small { height:10px; border-radius:8px; overflow:hidden; background:#eef2ff; }
  .progress-small > .bar { height:100%; background:linear-gradient(90deg,#1680ff,#0b6ef6); }

  /* Recent lists */
  .recent-name { font-weight:700; color:#0b2540; }
  .recent-meta { font-size:0.85rem; color:#64748b; }
  .recent-amount { font-weight:800; color:#0b6ef6; }

  /* Intakes list */
  .intake-name { font-weight:700; color:#0b2540; }
  .intake-meta { color:#64748b; font-size:0.875rem; }

  /* Expenses card */
  .expense-amount { font-weight:800; color:#c2410c; }
  .expense-count { font-weight:700; color:#071033; }

  /* Utility */
  .muted-note { color:#94a3b8; font-size:0.85rem; }
  .badge-compact { font-size:0.78rem; padding:6px 8px; border-radius:8px; }
  .small-card { background:#fff; border-radius:12px; padding:14px; box-shadow:0 6px 18px rgba(10,25,47,0.04); }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h3 class="mb-0">Secretary Dashboard</h3>
    <div class="muted-note">Overview of intakes, payments, expenses and actions</div>
  </div>
  <div>
    @if($activeIntake)
      <a href="{{ route('secretary.students.create', $activeIntake->id) }}" class="btn btn-primary">Register Student</a>
    @else
      <a href="#" class="btn btn-outline-secondary disabled">No active intake</a>
    @endif
  </div>
</div>

@php
  $usdRate = 3600;
  $totalReceiptsUGX = $ugxThisMonth + ($usdThisMonth * $usdRate);
  $currentBalanceUGX = $totalReceiptsUGX - $expensesThisMonth;
  $currentBalanceUSD = $currentBalanceUGX / $usdRate;
@endphp

<div class="row g-3 mb-3">
  <!-- Active Intakes -->
  <div class="col-md-3">
    <div class="card dashboard-card small-card">
      <div class="d-flex justify-content-between">
        <div>
          <div class="metric-title">Active Intakes</div>
          <div class="metric-value">{{ $activeIntakeCount }}</div>
          <div class="metric-sub mt-1">Currently active cohorts</div>
        </div>
        <div class="text-end">
          <span class="badge bg-light text-primary badge-compact"><i class="bi bi-calendar3 me-1"></i> Intakes</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Total Students -->
  <div class="col-md-3">
    <div class="card dashboard-card small-card">
      <div class="d-flex justify-content-between">
        <div>
          <div class="metric-title">Total Students</div>
          <div class="metric-value">{{ $studentCount }}</div>
          <div class="metric-sub mt-1">All enrolled students</div>
        </div>
        <div class="text-end">
          <span class="badge bg-light text-success badge-compact"><i class="bi bi-people me-1"></i> Learners</span>
        </div>
      </div>
    </div>
  </div><!-- Expenses (All-time) -->
<div class="col-md-3">
  <div class="card dashboard-card small-card">
    <div class="d-flex justify-content-between">
      <div class="d-flex justify-content-between align-items-center mt-2 p-2 rounded bg-light border">
        <div class="muted-note fw-semibold text-secondary">Grand Total</div>
        <div class="expense-total fw-bold text-success">
          UGX {{ number_format($totalExpensesAllTime ?? 0, 2) }}
        </div>
      </div>
      <div class="text-end">
        <span class="badge bg-light text-danger badge-compact">
          <i class="bi bi-receipt me-1"></i> Expenses
        </span>
      </div>
    </div>
    <div class="mt-3">
      <div class="mt-2">
        <a href="{{ route('secretary.expenses.index') }}" class="btn btn-sm btn-outline-secondary">View expenses</a>
        <a href="{{ route('secretary.expenses.create') }}" class="btn btn-sm btn-primary ms-2">Add expense</a>
      </div>
    </div>
  </div>
</div>

<!-- Current Balance (Month-to-date) -->
<div class="col-md-3">
  <div class="card dashboard-card small-card">
    <div class="d-flex justify-content-between">
      <div>
        <div class="metric-title">Current Balance (This Month)</div>
        <div class="metric-value text-success">
          UGX {{ number_format($currentBalanceUGXMonth ?? 0, 2) }}
        </div>
        <div class="metric-sub mt-1">
          ≈ USD {{ number_format($currentBalanceUSDMonth ?? 0, 2) }}
        </div>
      </div>
      <div class="text-end">
        <span class="badge bg-light text-success badge-compact">
          <i class="bi bi-cash-coin me-1"></i> Net
        </span>
      </div>
    </div>

    <div class="mt-3">
      <div class="d-flex justify-content-between">
        <div class="muted-note">Receipts</div>
        <div class="expense-count text-primary fw-semibold">
          UGX {{ number_format($totalReceiptsUGXMonth ?? 0, 2) }}
        </div>
      </div>
      <div class="d-flex justify-content-between">
       
      </div>
    </div>
  </div>
</div>

<!-- Current Balance (All-time) -->
<div class="col-md-3">
  <div class="card dashboard-card small-card">
    <div class="d-flex justify-content-between">
      <div>
        <div class="metric-title">Current Balance (All-time)</div>
        <div class="metric-value text-success">
          UGX {{ number_format($currentBalanceUGXAll ?? 0, 2) }}
        </div>
        <div class="metric-sub mt-1">
          ≈ USD {{ number_format($currentBalanceUSDAll ?? 0, 2) }}
        </div>
      </div>
      <div class="text-end">
        <span class="badge bg-light text-success badge-compact">
          <i class="bi bi-cash-coin me-1"></i> Net
        </span>
      </div>
    </div>
  </div>
</div>

<!-- Payments Summary -->
<div class="payments-card-content">
  <div class="card-head">
    <div>
      <div class="metric-title">Payments This Month</div>
      <div class="muted-note small">
        Period: {{ now()->format('F Y') }} · Last updated: {{ now()->format('d M Y H:i') }}
      </div>
    </div>
  </div>

  <div class="payments-main">
    <div class="payments-totals">
      <div class="payments-total">
        <span class="payments-currency-badge">UGX</span>
        <div class="payments-number">{{ number_format((float) $ugxThisMonth, 2) }}</div>
        <div class="muted-note">Local receipts</div>
      </div>

      <div class="payments-total">
        <span class="payments-currency-badge">USD</span>
        <div class="payments-number">{{ number_format((float) $usdThisMonth, 2) }}</div>
        <div class="muted-note">Foreign receipts</div>
      </div>
    </div>

    <div class="expected-grid">
      <div class="expected-item">
        <div class="expected-label">Expected (UGX)</div>
        <div class="expected-value">UGX {{ number_format((float) $expectedUGX, 2) }}</div>
      </div>

      <div class="expected-item text-end">
        <div class="expected-label">Outstanding</div>
        <div class="outstanding-value">UGX {{ number_format((float) $outstandingUGX, 2) }}</div>
      </div>

      <div class="expected-progress" aria-hidden="false" role="progressbar"
           aria-valuenow="{{ $collectedPct }}" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-track">
          <div class="progress-fill" style="width: {{ $collectedPct }}%"></div>
        </div>
        <div class="muted-note small mt-2">{{ $collectedPct }}% collected of expected UGX</div>
      </div>
    </div>
  </div>
</div>

<!-- Recent Payments -->
<div class="row g-3">
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Recent Payments</h5>
        <small class="muted-note">Latest {{ $recentPayments->count() ?? 0 }} receipts</small>
      </div>

      <ul class="list-group list-group-flush">
        @forelse($recentPayments ?? collect() as $p)
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="recent-name">{{ optional($p->student)->first_name ?? 'Unknown' }} {{ optional($p->student)->last_name ?? '' }}</div>
              <div class="recent-meta">
                {{ $p->reference ?? '—' }} • {{ \Carbon\Carbon::parse($p->paid_at)->format('d M Y') }}
                @if(!empty($p->note))
                  • <span class="text-muted small">{{ $p->note }}</span>
                @endif
              </div>
            </div>
            <div class="text-end">
              <div class="recent-amount">{{ $p->currency }} {{ number_format($p->amount, 2) }}</div>
              <div class="muted-note">{{ $p->method ?? '' }}</div>
            </div>
          </li>
        @empty
          <li class="list-group-item text-center text-muted">No payments found</li>
        @endforelse
      
      </ul>
    </div>
  </div>

  <!-- Active Intakes -->
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Active Intakes</h5>
        <small class="muted-note">Current cohorts overview</small>
      </div>

      <ul class="list-group list-group-flush">
        @forelse($intakes as $intake)
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="intake-name">{{ $intake->name }}</div>
              <div class="intake-meta">{{ \Carbon\Carbon::parse($intake->start_date)->format('d M Y') }}</div>
            </div>

            <div class="text-end">
              <div class="badge bg-light text-primary badge-compact">
                {{ $intake->students_count ?? $intake->students()->count() }} students
              </div>

              @php
                $intakeExpected = ($intake->students_count ?? $intake->students()->count()) * 300000;
                $intakeCollected = \App\Models\Payment::where('intake_id', $intake->id)->where('currency','UGX')->sum('amount');
                $intakeProgress = $intakeExpected > 0 ? min(100, intval(($intakeCollected / $intakeExpected) * 100)) : 0;
              @endphp

              <div class="mt-2">
                <div class="progress-small" style="width:140px;">
                  <div class="bar" style="width: {{ $intakeProgress }}%"></div>
                </div>
                <div class="muted-note" style="font-size:0.75rem;">{{ $intakeProgress }}% paid</div>
              </div>
            </div>
          </li>
        @empty
          <li class="list-group-item text-center text-muted">No active intakes</li>
        @endforelse
      </ul>
    </div>
  </div>
</div>

@endsection