{{-- resources/views/secretary/students/show.blade.php --}}
@extends('layouts.app')

@section('title', $student->first_name . ' ' . $student->last_name)

@section('content')
@php
  use Carbon\Carbon;

  $planKey   = $student->plan_key ?? 'physical_mentorship';
  $planCfg   = $planKey ? (config("plans.plans.$planKey") ?? null) : null;
  $planLabel = $planCfg['label'] ?? ($planKey ?? 'Unknown');

  // Original plan price from config (display only)
  if ($planCfg && isset($planCfg['price'])) {
      $origCurrency = strtoupper($planCfg['currency'] ?? 'UGX');
      $origPrice    = number_format($planCfg['price'], 2);
      $originalDisplay = "{$origCurrency} {$origPrice}";
  } else {
      $originalDisplay = 'UGX ' . number_format($student->course_fee ?? 0, 0);
  }

  // Totals in UGX (use amount_converted when available)
  $totalPaidUGX = $payments->sum(fn($p) => (float) ($p->amount_converted ?? 0));
  $balanceUGX   = max((float) ($student->course_fee ?? 0) - $totalPaidUGX, 0);

  $formatDate   = fn($d) => $d ? Carbon::parse($d)->format('d M Y H:i') : '—';

  // Phone display: prefer phone_full, then combine country code + phone, else dash
  if (!empty($student->phone_full)) {
      $phoneDisplay = $student->phone_full;
  } elseif (!empty($student->phone_country_code) || !empty($student->phone)) {
      $dial = !empty($student->phone_country_code) ? ('+' . ltrim($student->phone_country_code, '+')) : '';
      $phoneDisplay = trim($dial . ' ' . ($student->phone ?? ''));
  } else {
      $phoneDisplay = '—';
  }
@endphp

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
  <div>
    <h3 class="mb-0">{{ $student->first_name }} {{ $student->last_name }}</h3>
    <div class="muted-small">
      Intake: {{ $student->intake->name ?? '—' }} |
      Mentorship: {{ $planLabel }} |
      Price: {{ $originalDisplay }}
    </div>
  </div>

  <div class="d-flex gap-2 mt-2 mt-md-0">
    <a href="{{ route('secretary.payments.create', ['student' => $student->id]) }}" class="btn btn-success">Add Payment</a>

    {{-- Uncomment if needed --}}
    {{-- <a href="{{ route('secretary.payments.receipt', $student->id) }}" class="btn btn-outline-secondary" target="_blank">Receipt</a>
    <a href="#" onclick="window.print(); return false;" class="btn btn-outline-primary">Print Summary</a> --}}
  </div>
</div>

<div class="row g-3">
  {{-- Left column: student info (full width on small screens) --}}
  <div class="col-12 col-lg-4">
    <div class="card p-3 h-100">
      <h6 class="muted-small">Student Info</h6>
      <div class="mt-2">
        <div><strong>ID:</strong> {{ $student->id }}</div>
        <div><strong>Phone:</strong> {{ $phoneDisplay ?? ($student->phone ?? $brand['phone'] ?? '—') }}</div>
        <div><strong>Email:</strong> {{ $student->email ?? '—' }}</div>
        <div class="mt-1"><strong>Status:</strong> <span class="badge bg-light text-dark">{{ $student->status ?? '—' }}</span></div>

        <hr>

        <div><strong>Total Price (UGX):</strong> UGX {{ number_format($student->course_fee ?? 0, 0) }}</div>
        <div><strong>Total Paid:</strong> UGX {{ number_format($totalPaidUGX, 0) }}</div>
        <div><strong>Balance:</strong>
          @if($balanceUGX <= 0)
            <span class="text-success">Paid in full</span>
          @else
            <span class="text-danger">UGX {{ number_format($balanceUGX, 0) }}</span>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Right column: payments --}}
  <div class="col-12 col-lg-8">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Payments</h6>
        <div class="muted-small">Showing {{ $payments->count() }} payment(s)</div>
      </div>

      {{-- Responsive table wrapper --}}
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">Date</th>
              <th scope="col">Original</th>
              {{-- Hide less-critical columns on very small screens --}}
              <th scope="col" class="d-none d-sm-table-cell">Converted (UGX)</th>
              <th scope="col" class="d-none d-md-table-cell">Method</th>
              <th scope="col" class="d-none d-md-table-cell">Reference</th>
              <th scope="col" class="text-end">Actions</th>
            </tr>
          </thead>

          <tbody>
            @forelse($payments as $p)
              @php
                // Unique collapse id per payment for mobile details
                $collapseId = 'paymentDetails' . $p->id;
              @endphp

              <tr class="d-table-row">
                <td class="muted-small" style="min-width:120px;">
                  {{ $formatDate($p->paid_at ?? $p->created_at) }}
                </td>

                <td style="min-width:140px;">
                  {{ strtoupper($p->currency ?? 'UGX') }} {{ number_format((float)$p->amount, 2) }}
                </td>

                <td class="d-none d-sm-table-cell" style="min-width:120px;">
                  UGX {{ number_format((float)($p->amount_converted ?? 0), 0) }}
                </td>

                <td class="d-none d-md-table-cell muted-small" style="min-width:110px;">
                  {{ $p->method ?? '—' }}
                </td>

                <td class="d-none d-md-table-cell muted-small" style="min-width:140px;">
                  {{ $p->reference ?? $p->receipt_number ?? '—' }}
                </td>

                <td class="text-end" style="min-width:90px;">
                  {{-- On small screens show a toggle to reveal hidden details; on larger screens show Print --}}
                  <div class="d-flex justify-content-end gap-1">
                    <a href="{{ route('secretary.payments.receipt', $p->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" aria-label="Print receipt for payment {{ $p->id }}">Print</a>

                    {{-- Collapse toggle visible only on small screens --}}
                    <button class="btn btn-sm btn-outline-secondary d-inline-block d-md-none"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#{{ $collapseId }}"
                            aria-expanded="false"
                            aria-controls="{{ $collapseId }}">
                      Details
                    </button>
                  </div>
                </td>
              </tr>

              {{-- Collapsible details row for small screens --}}
              <tr class="d-md-none">
                <td colspan="6" class="p-0 border-0">
                  <div class="collapse" id="{{ $collapseId }}">
                    <div class="p-2 bg-light">
                      <div class="row g-1">
                        <div class="col-6"><strong>Converted</strong><div>UGX {{ number_format((float)($p->amount_converted ?? 0), 0) }}</div></div>
                        <div class="col-6"><strong>Method</strong><div class="muted-small">{{ $p->method ?? '—' }}</div></div>
                        <div class="col-12 mt-1"><strong>Reference</strong><div class="muted-small">{{ $p->reference ?? $p->receipt_number ?? '—' }}</div></div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center muted-small">No payments recorded yet</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Totals area --}}
      <div class="d-flex flex-column flex-sm-row justify-content-end gap-3 mt-3">
        <div class="text-end">
          <div class="muted-small">Subtotal (UGX)</div>
          <div class="fw-bold">UGX {{ number_format($planPriceUGX ?? $student->course_fee ?? 0, 0) }}</div>
        </div>

        <div class="text-end">
          <div class="muted-small">Paid</div>
          <div class="fw-bold">UGX {{ number_format($totalPaidUGX, 0) }}</div>
        </div>

        <div class="text-end">
          <div class="muted-small">Balance</div>
          <div class="fw-bold text-{{ $balanceUGX <= 0 ? 'success' : 'danger' }}">
            {{ $balanceUGX <= 0 ? 'Paid' : 'UGX ' . number_format($balanceUGX, 0) }}
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection