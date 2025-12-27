@extends('layouts.app')

@section('content')

<style>
  .dashboard-card { border-radius:12px; padding:18px; }
  .metric-title { font-size:0.9rem; color:#64748b; font-weight:600; letter-spacing:0.2px; }
  .metric-value { font-size:1.5rem; font-weight:700; color:#071033; margin-top:6px; }
  .metric-sub { font-size:0.875rem; color:#475569; }
  .payments-card-content { display:block; width:100%; box-sizing:border-box; }
  .card-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:12px; }
  .payments-main { display:flex; flex-direction:column; gap:12px; }
  .payments-totals { display:flex; gap:18px; align-items:flex-start; flex-wrap:wrap; }
  .payments-total { min-width:180px; flex:1 1 220px; display:flex; flex-direction:column; gap:6px; }
  .payments-currency-badge { display:inline-block; background:#eef2ff; color:#0b6ef6; font-weight:700; padding:6px 8px; border-radius:8px; font-size:0.85rem; }
  .payments-number { font-size:1.25rem; font-weight:800; color:#071033; margin-top:4px; white-space:nowrap; text-overflow:ellipsis; overflow:hidden; }
  .expected-grid { display:grid; grid-template-columns: 1fr 1fr; gap:12px 18px; align-items:start; }
  .expected-item { min-width:0; }
  .expected-label { font-size:0.85rem; color:#475569; font-weight:600; }
  .expected-value { font-size:1rem; color:#0b6ef6; font-weight:800; margin-top:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .outstanding-value { color:#c2410c; font-weight:800; font-size:1rem; margin-top:4px; }
  .expected-progress { grid-column: 1 / -1; margin-top:6px; }
  .progress-track { width:100%; height:12px; background:#eef2ff; border-radius:999px; overflow:hidden; }
  .progress-fill { height:100%; background:linear-gradient(90deg,#1680ff,#0b6ef6); transition:width 600ms ease; }
  .recent-name { font-weight:700; color:#0b2540; }
  .recent-meta { font-size:0.85rem; color:#64748b; }
  .recent-amount { font-weight:800; color:#0b6ef6; }
  .muted-note { color:#94a3b8; font-size:0.85rem; }
  .badge-compact { font-size:0.78rem; padding:6px 8px; border-radius:8px; }
  .small-card { background:#fff; border-radius:12px; padding:14px; box-shadow:0 6px 18px rgba(10,25,47,0.04); }
  .error-note { color:#b91c1c; font-size:0.8rem; }
  .summary-card { display:flex; gap:12px; align-items:center; justify-content:space-between; padding:12px; border-radius:12px; background:#fff; box-shadow:0 6px 18px rgba(10,25,47,0.04); }
  .summary-label { font-size:0.9rem; color:#475569; font-weight:600; }
  .summary-value { font-size:1.1rem; font-weight:800; color:#071033; }
  @media (max-width: 768px) {
    .expected-grid { grid-template-columns: 1fr; }
    .payments-number { font-size:1.05rem; }
    .summary-card { flex-direction:column; align-items:flex-start; gap:6px; }
  }
  
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h3 class="mb-0">Secretary Dashboard</h3>
    <div class="muted-note">Overview of intakes, payments, expenses and actions</div>
  </div>
  <div>
    @if(!empty($activeIntake))
      <a href="{{ route('secretary.students.create', $activeIntake->id) }}" class="btn btn-primary">Register Student</a>
    @else
      <a href="#" class="btn btn-outline-secondary disabled">No active intake</a>
    @endif
  </div>
</div>

@php
  // Defensive defaults
  $expectedUGX = $expectedUGX ?? 0;
  $outstandingUGX = $outstandingUGX ?? 0;
  $collectedPct = $collectedPct ?? 0;
  $ugxThisMonth = $ugxThisMonth ?? 0;
  $usdThisMonth = $usdThisMonth ?? 0;
  $collectedUGXAll = $collectedUGXAll ?? 0;
  $currentBalanceUGXAll = $currentBalanceUGXAll ?? 0;
  $currentBalanceUSDAll = $currentBalanceUSDAll ?? 0;
  $receiptsMinusExpensesAll = $receiptsMinusExpensesAll ?? ($collectedUGXAll - ($totalExpensesAll ?? 0));
@endphp

<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card dashboard-card small-card">  
      <div class="d-flex justify-content-between">
        <div>
          <div class="metric-title">Active Intakes</div>
          <div class="metric-value">{{ $activeIntakeCount ?? 0 }}</div>
          <div class="metric-sub mt-1">Currently active cohorts</div>
        </div>
        <div class="text-end">
          <span class="badge bg-light text-primary badge-compact"><i class="bi bi-calendar3 me-1"></i> Intakes</span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card dashboard-card small-card">
      <div class="d-flex justify-content-between">
        <div>
          <div class="metric-title">Total Students</div>
          <div class="metric-value">{{ $studentCount ?? 0 }}</div>
          <div class="metric-sub mt-1">All enrolled students</div>
        </div>
        <div class="text-end">
          <span class="badge bg-light text-success badge-compact"><i class="bi bi-people me-1"></i> Learners</span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card dashboard-card small-card">
      <div class="d-flex justify-content-between">
        <div>
          <div class="metric-title">Total Collected (All time)</div>
          <div class="metric-value">UGX {{ number_format($collectedUGXAll ?? 0, 2) }}</div>
          <div class="metric-sub mt-1">All receipts converted to UGX</div>
        </div>
        <div class="text-end">
          {{-- <span class="badge bg-light text-primary badge-compact"><i class="bi bi-cash-stack me-1"></i> Collected</span> --}}
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card dashboard-card small-card">
      <div class="d-flex justify-content-between">
        <div>
          <div class="metric-title">Total Balance (All time)</div>
          <div class="metric-value">UGX {{ number_format($currentBalanceUGXAll ?? 0, 2) }}</div>
          <div class="metric-sub mt-1">≈ USD {{ number_format($currentBalanceUSDAll ?? 0, 2) }}</div>
        </div>
        <div class="text-end">
          {{-- <span class="badge bg-light text-success badge-compact"><i class="bi bi-wallet2 me-1"></i> Balance</span> --}}
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Receipts minus expenses (all time) summary -->
<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="summary-card">
      <div>
        <div class="summary-label">Receipts minus expenses (All time)</div>
        <div class="summary-value">UGX {{ number_format($receiptsMinusExpensesAll ?? 0, 2) }}</div>
      </div>
      <div class="text-end">
        <div class="muted-note">Total receipts (UGX): <strong>{{ number_format($collectedUGXAll ?? 0, 2) }}</strong></div>
        <div class="muted-note">Total expenses (UGX): <strong>{{ number_format($totalExpensesAll ?? 0, 2) }}</strong></div>
      </div>
    </div>
  </div>
</div>

<!-- Payments Summary -->
<div class="payments-card-content mb-3">
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
        <div class="payments-number">{{ number_format((float) ($ugxThisMonth ?? 0), 2) }}</div>
        <div class="muted-note">Local receipts</div>
      </div>

      <div class="payments-total">
        <span class="payments-currency-badge">USD</span>
        <div class="payments-number">{{ number_format((float) ($usdThisMonth ?? 0), 2) }}</div>
        <div class="muted-note">Foreign receipts</div>
      </div>
    </div>

<div class="expected-grid">
  <div class="expected-item">
    <div class="expected-label">Expected (UGX)</div>
    <div class="expected-value">UGX {{ number_format((float) ($expectedUGXAll ?? $expectedUGX ?? 0), 2) }}</div>
  </div>

  <div class="expected-item text-end">
    <div class="expected-label">Outstanding</div>
    <div class="outstanding-value">
      UGX {{ number_format((float) (max(0, ($expectedUGXAll ?? $expectedUGX ?? 0) - ($collectedUGXAll ?? 0))), 2) }}
    </div>
  </div>

  <div class="expected-progress" role="progressbar"
       aria-valuenow="{{ $collectedPctAll ?? 0 }}" aria-valuemin="0" aria-valuemax="100">
    <div class="progress-track">
      <div class="progress-fill" style="width: {{ $collectedPctAll ?? 0 }}%"></div>
    </div>
    <div class="muted-note small mt-2">
      {{ number_format($collectedPctAll ?? 0, 2) }}% collected of expected UGX (all time)
      · Collected: UGX {{ number_format((float) ($collectedUGXAll ?? 0), 2) }}
    </div>
  </div>
</div>
  </div>
</div>

<!-- Recent Payments & Active Intakes -->
<div class="row g-3">
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Recent Payments</h5>
        <small class="muted-note">Latest {{ $recentPayments->count() ?? 0 }} receipts</small>
      </div>

      <ul class="list-group list-group-flush">
        @forelse($recentPayments as $p)
          @php
            $studentName = 'Unknown';
            if (!empty($p->student) && (!empty($p->student->first_name) || !empty($p->student->last_name))) {
                $studentName = trim(($p->student->first_name ?? '') . ' ' . ($p->student->last_name ?? ''));
            }

            $paidAt = $p->paid_at ? $p->paid_at->format('d M Y') : '—';
            $reference = $p->reference ?: '—';
            $note = $p->note ?: null;
            $method = $p->method ?: '—';
            $amountDisplay = number_format($p->amount ?? 0, 2);
            $currency = $p->currency ?? 'UGX';

            $missing = [];
            if (($p->amount ?? 0) == 0) $missing[] = 'amount';
            if ($studentName === 'Unknown') $missing[] = 'student';
            if ($paidAt === '—') $missing[] = 'paid_at';
            $debugNote = count($missing) ? 'Missing: ' . implode(', ', $missing) : null;
          @endphp

          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="recent-name">{{ $studentName }}</div>
              <div class="recent-meta">
                {{ $reference }} • {{ $paidAt }}
                @if($note)
                  • <span class="text-muted small">{{ $note }}</span>
                @endif
                @if($debugNote)
                  <div class="error-note mt-1">{{ $debugNote }}</div>
                @endif
              </div>
            </div>

            <div class="text-end">
              <div class="recent-amount">{{ $currency }} {{ $amountDisplay }}</div>
              <div class="muted-note">{{ $method }}</div>
              <div class="small mt-1"><a href="{{ route('secretary.payments.show', $p->id) }}">View</a></div>
            </div>
          </li>
        @empty
          <li class="list-group-item text-center text-muted">No payments found</li>
        @endforelse
      </ul>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Active Intakes</h5>
        <small class="muted-note">Current cohorts overview</small>
      </div>

      <ul class="list-group list-group-flush">
        @forelse($intakes as $intake)
          @php
            $studentsCount = $intake->students_count ?? (method_exists($intake, 'students') ? $intake->students()->count() : 0);
            $intakeExpected = ($studentsCount) * 300000;
            $intakeCollected = \App\Models\Payment::where('intake_id', $intake->id)->where('currency','UGX')->sum('amount');
            $intakeProgress = $intakeExpected > 0 ? min(100, intval(($intakeCollected / $intakeExpected) * 100)) : 0;
          @endphp

          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="intake-name">{{ $intake->name ?? '—' }}</div>
              <div class="intake-meta">{{ $intake->start_date ? \Carbon\Carbon::parse($intake->start_date)->format('d M Y') : '—' }}</div>
            </div>

            <div class="text-end">
              <div class="badge bg-light text-primary badge-compact">
                {{ $studentsCount }} students
              </div>

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