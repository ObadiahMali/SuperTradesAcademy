{{-- resources/views/secretary/intakes/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Intake Details')

@section('content')
@php
use Carbon\Carbon;

/*
  Prepare collections:
  - $students: students in this intake (assumes $intake->students relationship exists)
  - $payments: all payments made by students in this intake
  - $paymentsByCurrency: grouped sums by currency
  - $paymentsThisMonth: payments in current period (month)
*/
$students = $intake->relationLoaded('students') ? $intake->students : $intake->students()->with('payments')->get();

$payments = $students->flatMap(function($s) {
    return $s->relationLoaded('payments') ? $s->payments : $s->payments()->get();
});

$paymentsByCurrency = $payments
    ->groupBy(fn($p) => strtoupper($p->currency ?? 'UGX'))
    ->map(fn($group) => $group->sum(fn($p) => (float) $p->amount));

$paymentsCount = $payments->count();

$startOfMonth = now()->startOfMonth();
$endOfMonth = now()->endOfMonth();

$paymentsThisMonth = $payments->filter(fn($p) => $p->paid_at && Carbon::parse($p->paid_at)->between($startOfMonth, $endOfMonth));
$paymentsThisMonthByCurrency = $paymentsThisMonth
    ->groupBy(fn($p) => strtoupper($p->currency ?? 'UGX'))
    ->map(fn($group) => $group->sum(fn($p) => (float) $p->amount));
$paymentsThisMonthCount = $paymentsThisMonth->count();
@endphp

<div class="row g-3">
  <div class="col-12">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <h3 class="mb-1">{{ $intake->name }}</h3>
          <div class="muted-note small">
            {{ $intake->start_date ? \Carbon\Carbon::parse($intake->start_date)->format('d M Y') : '—' }}
            @if($intake->end_date)
              — {{ \Carbon\Carbon::parse($intake->end_date)->format('d M Y') }}
            @endif
            · Created {{ $intake->created_at->format('d M Y H:i') }}
          </div>
        </div>

        <div class="text-end">
          @if($intake->active)
            <span class="badge bg-success">Active</span>
          @else
            <span class="badge bg-light text-muted">Inactive</span>
          @endif
        </div>
      </div>

      <hr class="my-3">

      {{-- Summary cards --}}
      <div class="row g-3 mb-3">
        <div class="col-md-3">
          <div class="card p-3 h-100">
            <div class="muted-note small">Students</div>
            <div class="h4 mb-0">{{ number_format($students->count()) }}</div>
            <div class="muted-note small mt-1">Total enrolled in this intake</div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card p-3 h-100">
            <div class="muted-note small">Payments (All-time)</div>
            <div class="h4 mb-0">{{ number_format($paymentsCount) }}</div>
            <div class="muted-note small mt-1">Total payment records</div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card p-3 h-100">
            <div class="muted-note small">Payments (This Month)</div>
            <div class="h4 mb-0">{{ number_format($paymentsThisMonthCount) }}</div>
            <div class="muted-note small mt-1">Records in {{ now()->format('F Y') }}</div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card p-3 h-100">
            <div class="muted-note small">Currencies Received</div>
            <div class="h6 mb-0">
              @if($paymentsByCurrency->isEmpty())
                <span class="text-muted">—</span>
              @else
                @foreach($paymentsByCurrency as $cur => $amt)
                  <div>{{ $cur }}: {{ number_format($amt, 2) }}</div>
                @endforeach
              @endif
            </div>
            <div class="muted-note small mt-1">Grouped by currency</div>
          </div>
        </div>
      </div>
{{-- Totals by currency (all-time) --}}
<div class="mb-3 section-card">
  <h5 class="collected-heading">Collected (All-time) by Currency</h5>

  @if($paymentsByCurrency->isEmpty())
    <div class="text-muted">No payments recorded for this intake yet.</div>
  @else
    <div class="badge-row">
      @foreach($paymentsByCurrency as $currency => $amount)
        <div class="currency-badge">
          <strong>{{ $currency }}</strong>&nbsp; {{ number_format($amount, 2) }}
        </div>
      @endforeach
    </div>
  @endif
</div>

{{-- Totals by currency (this month) --}}
<div class="mb-3 section-card">
  <h6 class="collected-heading">Collected ({{ now()->format('F Y') }}) by Currency</h6>

  @if($paymentsThisMonthByCurrency->isEmpty())
    <div class="text-muted">No payments this month.</div>
  @else
    <div class="badge-row">
      @foreach($paymentsThisMonthByCurrency as $currency => $amount)
        <div class="currency-badge">
          <strong>{{ $currency }}</strong>&nbsp; {{ number_format($amount, 2) }}
        </div>
      @endforeach
    </div>
  @endif
</div>

      <hr>

      {{-- Students table with collected amounts --}}
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Student</th>
              <th>Phone</th>
              <th class="text-center">Enrolled</th>
              <th class="text-end">Collected (per currency)</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($students as $student)
              @php
                $studentPayments = $student->relationLoaded('payments') ? $student->payments : $student->payments()->get();
                $studentByCurrency = $studentPayments
                    ->groupBy(fn($p) => strtoupper($p->currency ?? 'UGX'))
                    ->map(fn($g) => $g->sum(fn($p) => (float) $p->amount));
              @endphp
              <tr>
                <td>
                  <a href="{{ route('secretary.students.show', $student) }}" class="text-decoration-none">
                    {{ $student->first_name }} {{ $student->last_name }}
                  </a>
                  @if($student->course)
                    <div class="muted-note small mt-1">{{ $student->course }}</div>
                  @endif
                </td>

                <td>{{ $student->phone ?? '—' }}</td>

                <td class="text-center">
                  {{ $student->created_at ? $student->created_at->format('d M Y') : '—' }}
                </td>

                <td class="text-end">
                  @if($studentByCurrency->isEmpty())
                    <span class="text-muted">No payments</span>
                  @else
                    @foreach($studentByCurrency as $cur => $amt)
                      <div>{{ $cur }} {{ number_format($amt, 2) }}</div>
                    @endforeach
                  @endif
                </td>

                <td class="text-end">
                  <a href="{{ route('secretary.payments.create', $student) }}" class="btn btn-sm btn-outline-primary me-1">Add Payment</a>
                  <a href="{{ route('secretary.students.show', $student) }}" class="btn btn-sm btn-outline-secondary">View</a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted">No students enrolled in this intake yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3 d-flex justify-content-between">
        <a href="{{ route('secretary.intakes.index') }}" class="btn btn-outline-secondary">Back to Intakes</a>

        <div>
          <a href="{{ route('secretary.intakes.edit', $intake) }}" class="btn btn-primary me-2">Edit Intake</a>

          <form action="{{ route('secretary.intakes.destroy', $intake) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Delete this intake?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection