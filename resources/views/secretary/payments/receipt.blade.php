<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Receipt - {{ $receipt->number ?? ($payment->receipt_number ?? 'N/A') }}</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --primary:#0b6ef6;
      --muted:#64748b;
      --accent:#0b2540;
      --border:#eef2f7;
      font-family: 'Inter', system-ui, sans-serif;
    }
    html,body{margin:0;padding:0;background:#fff;color:var(--accent);font-size:13px;line-height:1.4}
    .container{max-width:760px;margin:18px auto;padding:18px;border:1px solid #f5f7fb;border-radius:8px}
    .header,.footer{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap}
    .brand{display:flex;gap:12px;align-items:center}
    .logo{width:72px;height:72px;border-radius:10px;overflow:hidden;border:1px solid rgba(11,46,64,0.06);display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,#fff,#fbfdff);box-shadow:0 6px 18px rgba(11,46,64,0.04);padding:6px;flex-shrink:0}
    .logo img{width:100%;height:100%;object-fit:contain;display:block}
    .brand-text .name{font-weight:700;font-size:1rem;color:var(--primary)}
    .brand-text .tag{font-size:0.85rem;color:var(--muted)}
    .meta{text-align:right;color:var(--muted);font-size:0.9rem}
    hr{border:none;border-top:1px solid var(--border);margin:14px 0}
    .grid{display:flex;gap:18px;flex-wrap:wrap}
    .box{flex:1;min-width:260px}
    .box h4{margin:0 0 6px 0;font-size:0.85rem;color:var(--muted);font-weight:600}
    .payment-card{border:1px solid var(--border);padding:12px;border-radius:8px;background:#fbfdff}
    .amount{font-size:1.1rem;font-weight:700;color:var(--primary);text-align:right;margin-top:6px}
    table{width:100%;border-collapse:collapse;margin-top:12px;font-size:0.95rem}
    thead th{background:#fafbff;padding:8px;border:1px solid var(--border);text-align:left;color:var(--muted);font-weight:600}
    tbody td{padding:10px;border:1px solid var(--border);vertical-align:middle;background:#ffffff}
    .text-right{text-align:right}
    .muted{color:var(--muted)}
    .total-row td{font-weight:700;background:#ffffff}
    .badge{display:inline-block;padding:4px 8px;border-radius:6px;background:#f1f5f9;color:var(--accent);font-weight:700}
    .footer{margin-top:18px;align-items:flex-end}
    .contact{font-size:0.85rem;color:var(--muted);line-height:1.4}
    .verification{text-align:right;font-size:0.85rem;color:var(--muted)}
    .verification strong{display:block;color:var(--accent);font-weight:700;margin-top:4px}
    .qr{width:80px;height:80px;border-radius:6px;border:1px solid var(--border);display:inline-block;overflow:hidden}
    @media print{.no-print{display:none!important}.container{border:none;padding:10px}}
    .actions{display:flex;gap:8px;justify-content:center;margin:18px 0}
    .btn{padding:8px 12px;border-radius:6px;border:1px solid #e6eef8;background:#fff;color:var(--accent);font-weight:700;text-decoration:none}
    .btn.primary{background:var(--primary);color:#fff;border:none}
  </style>
</head>
<body>
  <div class="container">
    @php
      use Carbon\Carbon;

      // Brand / contact configuration (single place to change)
      $brand = [
        'name' => 'SuperTrades Academy',
        'tagline' => 'Practical skills. Real trades. Lasting careers.',
        'address' => 'Akamwesi Mall, Gayaza–Kampala Road, Kyebando',
        'hours' => 'Mon, Tue & Sat • 9:30 AM – 1:00 PM',
        'phone' => '+256 759 953041',
        'email' => 'info@supertrades.ac'
      ];

      // Resolve plan from config (single source)
      $planKey = $student->plan_key ?? 'physical_mentorship';
      $plan = config("pricing.plans.$planKey") ?? ['label' => 'Unknown', 'price' => 0, 'currency' => 'UGX'];

      // Convert plan price to UGX if needed and centralize course fee logic
      $convertedPlanPriceUGX = $plan['currency'] === 'USD'
          ? app(\App\Services\ExchangeRateService::class)->usdToUgx($plan['price'])
          : $plan['price'];

      // Use student->course_fee only if student currency is UGX (pre-converted), otherwise use converted plan price
      $studentCurrency = strtoupper($student->currency ?? 'UGX');
      $courseFeeUGX = $studentCurrency === 'UGX'
          ? ($student->course_fee ?? $convertedPlanPriceUGX)
          : $convertedPlanPriceUGX;

      // Totals computed once and re-used
      $payments = $student->payments; // ensure relation is loaded by controller
      $totalPaidUGX = $payments->sum('amount_converted');
      $dueUGX = max(0, $courseFeeUGX - $totalPaidUGX);

      // Helpers
      $formatDate = fn($d) => $d ? Carbon::parse($d)->format('d M Y H:i') : '—';
      $receiptNumber = $receipt->number ?? ($payment->receipt_number ?? null);
      $issuedAt = $receipt->issued_at ?? ($payment->paid_at ?? now());
      $qrPresent = isset($qrBase64) && $qrBase64;
    @endphp

    <!-- HEADER -->
    <div class="header">
      <div class="brand">
        <div class="logo">
          <img src="{{ asset('images/logo.png') }}" alt="{{ $brand['name'] }} logo">
        </div>
        <div class="brand-text">
          <div class="name">{{ $brand['name'] }}</div>
          <div class="tag">{{ $brand['tagline'] }}</div>
          <div class="tag">{{ $brand['address'] }}</div>
          <div class="tag">{{ $brand['hours'] }}</div>
        </div>
      </div>

      <div class="meta">
        <div style="font-weight:700">Payment Receipt</div>
        <div>Receipt: {{ $receiptNumber ?? 'N/A' }}</div>
        <div>Date: {{ $formatDate($issuedAt) }}</div>
        <div class="muted">Receipt for: {{ $plan['label'] }} ({{ $plan['currency'] }} {{ number_format($plan['price'], 2) }})</div>
      </div>
    </div>

    <hr>

    <!-- STUDENT + SUMMARY -->
    <div class="grid">
      <div class="box">
        <h4>Student</h4>
        <p><strong>{{ $student->first_name }} {{ $student->last_name }}</strong></p>
        <p>ID: {{ $student->id }}</p>
        <p>Intake: {{ optional($intake)->name ?? '—' }}</p>
        @if($student->email)<p>Email: {{ $student->email }}</p>@endif
        @if($student->phone)<p>Phone: {{ $student->phone }}</p>@endif
        <p>Status: <span class="badge">{{ $student->status ?? '—' }}</span></p>
      </div>

      <div class="box">
        <h4>Payment Summary</h4>
        <div class="payment-card">
          <div style="display:flex;justify-content:space-between;gap:12px;align-items:center">
            <div>
              <div class="muted">Plan</div>
              <div style="font-weight:700">{{ $plan['label'] }}</div>
              <div class="muted" style="margin-top:6px">Original: {{ $plan['currency'] }} {{ number_format($plan['price'], 2) }}</div>
            </div>

            <div style="min-width:160px;text-align:right">
              <div class="muted">Course Fee (UGX)</div>
              <div class="amount">UGX {{ number_format($courseFeeUGX, 2) }}</div>
            </div>
          </div>

          <hr style="margin:10px 0">

          <div style="display:flex;justify-content:space-between;gap:12px;align-items:center">
            <div>
              <div class="muted">Total Paid (UGX)</div>
              <div style="font-weight:700">UGX {{ number_format($totalPaidUGX, 2) }}</div>
            </div>
            <div style="text-align:right">
              <div class="muted">Balance</div>
              @if($dueUGX > 0)
                <div style="color:#e11d48;font-weight:700">UGX {{ number_format($dueUGX, 2) }}</div>
              @else
                <div style="color:#059669;font-weight:700">Paid in full</div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- PAYMENTS TABLE -->
    <table aria-label="Payments table">
      <thead>
        <tr>
          <th style="width:18%">Date</th>
          <th style="width:22%">Original</th>
          <th style="width:25%">Converted (UGX)</th>
          <th style="width:15%">Method</th>
          <th style="width:20%">Reference</th>
        </tr>
      </thead>
      <tbody>
        @forelse($payments->sortByDesc('paid_at') as $p)
          <tr>
            <td>{{ $formatDate($p->paid_at ?? $p->created_at) }}</td>
            <td>{{ ($p->currency ?? 'UGX') }} {{ number_format($p->amount, 2) }}</td>
            <td>UGX {{ number_format($p->amount_converted ?? 0, 2) }}</td>
            <td class="muted">{{ $p->method ?? '—' }}</td>
            <td class="muted">{{ $p->reference ?? ($p->receipt_number ?? '—') }}</td>
          </tr>
        @empty
          <tr><td colspan="5" class="muted text-right">No payments recorded</td></tr>
        @endforelse

        {{-- Totals lines (use the computed variables) --}}
        <tr class="total-row">
          <td colspan="2" class="muted">Subtotal (Course Fee)</td>
          <td class="text-right">UGX {{ number_format($courseFeeUGX, 2) }}</td>
          <td colspan="2"></td>
        </tr>

        <tr class="total-row">
          <td colspan="2" class="muted">Total Paid</td>
          <td class="text-right">UGX {{ number_format($totalPaidUGX, 2) }}</td>
          <td colspan="2"></td>
        </tr>

        <tr class="total-row">
          <td colspan="2" class="muted">Balance</td>
          <td class="text-right">
            @if($dueUGX > 0)
              UGX {{ number_format($dueUGX, 2) }}
            @else
              Paid
            @endif
          </td>
          <td colspan="2"></td>
        </tr>
      </tbody>
    </table>

    <!-- FOOTER -->
    <div class="footer">
      <div class="contact">
        <strong>{{ $brand['name'] }}</strong><br>
        {{ $brand['address'] }}<br>
        📞 {{ $brand['phone'] }} • ✉️ {{ $brand['email'] }}<br>
        {{ $brand['tagline'] }}
      </div>

      <div class="verification">
        <div>Received by: <strong>{{ $receipt->received_by_name ?? ($payment->created_by_name ?? '—') }}</strong></div>
        <div style="margin-top:8px">Verification code: <strong>{{ $receipt->verification_code ?? ($payment->verification_hash ?? '—') }}</strong></div>

        @if($qrPresent)
          <div style="margin-top:10px">
            <div class="qr"><img src="{{ $qrBase64 }}" alt="QR" style="width:100%;height:100%;object-fit:cover"></div>
            <div class="muted" style="margin-top:6px">Scan to verify</div>
          </div>
        @endif
      </div>
    </div>

    <div style="margin-top:14px;text-align:right">
      <img src="{{ asset('images/logo2.jfif') }}" alt="logo" style="width:64px;opacity:0.9;border-radius:6px">
    </div>
  </div>

  <!-- ACTIONS (no-print) -->
  <div class="no-print actions">
    <a class="btn" href="{{ route('secretary.students.show', $student->id) }}">Back to profile</a>
    <button class="btn" onclick="window.print()">Print</button>

    @php
      $pdfRouteName = 'secretary.payments.receipt.pdf';
      $pdfUrl = \Illuminate\Support\Facades\Route::has($pdfRouteName) && isset($payment->id)
          ? route($pdfRouteName, $payment->id)
          : null;
    @endphp

    @if($pdfUrl)
      <a class="btn primary" href="{{ $pdfUrl }}" target="_blank" rel="noopener">Download PDF</a>
    @else
      <button class="btn primary" onclick="alert('PDF not available')" aria-disabled="true">Download PDF</button>
    @endif
  </div>
</body>
</html>