@extends('layouts.print')
@section('title','Student Receipt')
@section('subtitle', $student->first_name.' '.$student->last_name)

@section('content')
@php
  use Carbon\Carbon;

  // fallbacks and formatting helpers
  $payments = $payments ?? $student->payments ?? collect();
  $planLabel = $planLabel ?? ($student->plan_key ?? 'Unknown');
  $originalDisplay = $originalDisplay ?? ('UGX ' . number_format($student->course_fee ?? 0, 2));
  $totalPaidUGX = $totalPaidUGX ?? $payments->sum(fn($p) => (float) ($p->amount_converted ?? 0));
  $balanceUGX = $balanceUGX ?? max((float) ($student->course_fee ?? 0) - $totalPaidUGX, 0);
  $formatDate = $formatDate ?? fn($d) => $d ? Carbon::parse($d)->format('d M Y H:i') : '—';

  // prefer first payment for receipt metadata and original display if present
  $firstPayment = $payments->first();
  if ($firstPayment) {
      $receiptNumber = $receiptNumber ?? $firstPayment->receipt_number ?? $firstPayment->reference ?? '—';
      $receiptDate = $receiptDate ?? $firstPayment->paid_at ?? $firstPayment->created_at ?? now();
      // If you prefer the Original to reflect the actual payment, uncomment the next line:
      // $originalDisplay = strtoupper($firstPayment->currency ?? 'UGX') . ' ' . number_format($firstPayment->amount, 2);
  } else {
      $receiptNumber = $receiptNumber ?? '—';
      $receiptDate = $receiptDate ?? now();
  }
@endphp

<style>
  .receipt { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial; color:#0b1220; }
  .header { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px; }
  .brand { display:flex; gap:12px; align-items:center; }
  .logo-box { width:72px; height:72px; background:#f3f4f6; border-radius:8px; display:flex; align-items:center; justify-content:center; font-weight:700; color:#0b5cff; }
  .muted { color:#6b7280; }
  .card { background:#fff; border:1px solid #eef2f7; border-radius:8px; padding:14px; }
  .summary-table td { padding:6px 0; }
  .payments-table { width:100%; border-collapse:collapse; margin-top:10px; }
  .payments-table th, .payments-table td { padding:10px 6px; border-bottom:1px solid #f3f6fb; text-align:left; }
  .actions a { color:#0b5cff; text-decoration:none; margin-left:12px; }
  .accent { color:#0b5cff; font-weight:700; }
  .paid { color:#16a34a; font-weight:700; }
  .owing { color:#c2410c; font-weight:700; }
</style>

<div class="receipt">
  <div class="header">
    <div class="brand">
      <div class="logo-box">STA</div>
      <div>
        <div style="font-weight:700; font-size:18px;">SuperTrades Academy</div>
        <div class="muted">Practical skills. Real trades. Lasting careers.</div>
        <div class="muted" style="font-size:13px;">Akamwesi Mall, Gayaza–Kampala Road, Kyebando</div>
        <div class="muted" style="font-size:13px;">Mon, Tue & Sat • 9:30 AM – 1:00 PM</div>
      </div>
    </div>

    <div style="text-align:right;">
      <div class="card" style="display:inline-block; text-align:left;">
        <div style="font-weight:700;">Payment Receipt</div>
        <div style="margin-top:6px;" class="muted">
          <div>Receipt: <span class="accent">{{ $receiptNumber }}</span></div>
          <div>Date: {{ $formatDate($receiptDate) }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:12px;">
    <div style="margin-bottom:8px;">
      <strong>Receipt for:</strong>
      <span>{{ $planLabel ?? 'Unknown' }} ({{ $originalDisplay }})</span>
    </div>

    <div style="display:flex; gap:24px;">
      <div style="flex:1;">
        <h5 style="margin:0 0 8px 0;">Student</h5>
        <div style="font-weight:700; font-size:16px;">{{ $student->first_name }} {{ $student->last_name }}</div>
        <div class="muted" style="margin-top:8px; line-height:1.5;">
          <div><strong>ID:</strong> {{ $student->id }}</div>
          <div><strong>Intake:</strong> {{ $student->intake->name ?? '—' }}</div>
          <div><strong>Email:</strong> {{ $student->email ?? '—' }}</div>
          <div><strong>Phone:</strong> {{ $student->phone ?? '—' }}</div>
          <div><strong>Status:</strong> {{ $student->status ?? '—' }}</div>
        </div>
      </div>

      <div style="width:340px;">
        <h5 style="margin:0 0 8px 0;">Payment Summary</h5>
        <table style="width:100%; border-collapse:collapse;">
          <tr class="summary-table">
            <td class="muted"><strong>Plan</strong></td>
            <td style="text-align:right;">{{ $planLabel }}</td>
          </tr>
          <tr class="summary-table">
            <td class="muted"><strong>Original</strong></td>
            <td style="text-align:right;">{{ $originalDisplay }}</td>
          </tr>
          <tr class="summary-table">
            <td class="muted"><strong>Course Fee (UGX)</strong></td>
            <td style="text-align:right;">UGX {{ number_format($student->course_fee ?? 0, 2) }}</td>
          </tr>
          <tr class="summary-table">
            <td class="muted"><strong>Total Paid (UGX)</strong></td>
            <td style="text-align:right;">UGX {{ number_format($totalPaidUGX, 2) }}</td>
          </tr>
          <tr class="summary-table">
            <td class="muted"><strong>Balance</strong></td>
            <td style="text-align:right;">
              @if($balanceUGX <= 0)
                <span class="paid">Paid in full</span>
              @else
                <span class="owing">UGX {{ number_format($balanceUGX, 2) }}</span>
              @endif
            </td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <div class="card">
    <h5 style="margin:0 0 8px 0;">Payments</h5>

    <table class="payments-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Original</th>
          <th style="text-align:right;">Converted (UGX)</th>
          <th>Method</th>
          <th>Reference</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($payments as $p)
          <tr>
            <td>{{ $formatDate($p->paid_at ?? $p->created_at) }}</td>
            <td>{{ strtoupper($p->currency ?? 'UGX') }} {{ number_format($p->amount, 2) }}</td>
            <td style="text-align:right;">UGX {{ number_format((float) ($p->amount_converted ?? 0), 2) }}</td>
            <td class="muted">{{ $p->method ?? '—' }}</td>
            <td class="muted">{{ $p->receipt_number ?? $p->reference ?? '—' }}</td>
            <td class="text-end">
              {{-- Use the payment-specific route if you added it, otherwise pass the student --}}
              <a href="{{ route('secretary.payments.receipt.payment', $p) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Print</a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align:center; color:#6b7280; padding:12px;">No payments recorded yet</td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div style="display:flex; justify-content:flex-end; gap:24px; margin-top:12px;">
      <div style="text-align:right;">
        <div class="muted">Subtotal (Course Fee)</div>
        <div style="font-weight:700;">UGX {{ number_format($student->course_fee ?? 0, 2) }}</div>
      </div>

      <div style="text-align:right;">
        <div class="muted">Total Paid</div>
        <div style="font-weight:700;">UGX {{ number_format($totalPaidUGX, 2) }}</div>
      </div>

      <div style="text-align:right;">
        <div class="muted">Balance</div>
        <div style="font-weight:700; color:{{ $balanceUGX <= 0 ? '#16a34a' : '#c2410c' }};">
          {{ $balanceUGX <= 0 ? 'Paid' : 'UGX ' . number_format($balanceUGX, 2) }}
        </div>
      </div>
    </div>
  </div>

  <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px;">
    <div>
      <div style="font-weight:700;">SuperTrades Academy</div>
      <div class="muted">Akamwesi Mall, Gayaza–Kampala Road, Kyebando</div>
      <div class="muted">📞 +256 759 953041 • ✉️ info@supertrades.ac</div>
      <div class="muted">Practical skills. Real trades. Lasting careers.</div>
    </div>

    <div style="text-align:right;">
      <div class="muted">Received by:</div>
      <div style="font-weight:700;">Fahad Swalle</div>
      <div class="muted">Verification code: dd3ce79403</div>

      <div style="margin-top:8px;">
        <a href="{{ url()->previous() }}" class="actions">Back to profile</a>
        <a href="#" onclick="window.print(); return false;" class="actions">Print</a>
      </div>
    </div>
  </div>
</div>
@endsection