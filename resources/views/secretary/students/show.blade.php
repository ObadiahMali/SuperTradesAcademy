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

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h3 class="mb-0">{{ $student->first_name }} {{ $student->last_name }}</h3>
    <div class="muted-small">
      Intake: {{ $student->intake->name ?? '—' }} |
      Mentorship: {{ $planLabel }} |
      Price: {{ $originalDisplay }}
    </div>
  </div>

  <div>
    <a href="{{ route('secretary.payments.create', ['student' => $student->id]) }}" class="btn btn-success me-2">Add Payment</a>

    {{-- <a href="{{ route('secretary.payments.receipt', $student->id) }}" class="btn btn-outline-secondary me-2" target="_blank">
      Receipt
    </a>

    <a href="#" onclick="window.print(); return false;" class="btn btn-sm btn-outline-primary">
      Print Summary
    </a> --}}
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card p-3">
      <h6 class="muted-small">Student Info</h6>
      <div class="mt-2">
        <div><strong>ID:</strong> {{ $student->id }}</div>
        <div><strong>Phone:</strong> {{ $phoneDisplay }}</div>
        <div><strong>Email:</strong> {{ $student->email ?? '—' }}</div>
        <div><strong>Status:</strong> <span class="badge bg-light text-dark">{{ $student->status ?? '—' }}</span></div>
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

  <div class="col-lg-8">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Payments</h6>
        <div class="muted-small">Showing {{ $payments->count() }} payment(s)</div>
      </div>

      <table class="table table-sm mt-2">
        <thead>
          <tr>
            <th>Date</th>
            <th>Original</th>
            <th>Converted (UGX)</th>
            <th>Method</th>
            <th>Reference</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($payments as $p)
            <tr>
              <td class="muted-small">{{ $formatDate($p->paid_at ?? $p->created_at) }}</td>
              <td>{{ strtoupper($p->currency ?? 'UGX') }} {{ number_format((float)$p->amount, 2) }}</td>
              <td>UGX {{ number_format((float)($p->amount_converted ?? 0), 0) }}</td>
              <td class="muted-small">{{ $p->method ?? '—' }}</td>
              <td class="muted-small">{{ $p->reference ?? $p->receipt_number ?? '—' }}</td>
              <td class="text-end">
                <a href="{{ route('secretary.payments.receipt', $p->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Print</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center muted-small">No payments recorded yet</td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="d-flex justify-content-end mt-3">
       <div class="me-4 text-end">
  <div class="muted-small">Subtotal (UGX)</div>
  <div class="fw-bold">UGX {{ number_format($planPriceUGX ?? $student->course_fee ?? 0, 0) }}</div>
</div>
        <div class="me-4 text-end">
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