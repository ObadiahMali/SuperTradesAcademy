<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Payment Receipt</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body { font-family: Arial, sans-serif; background:#f4f6f8; margin:0; padding:20px; }
    .container { max-width:600px; margin:0 auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.06); }
    .header { background:#0b5ed7; color:#fff; padding:16px; display:flex; align-items:center; gap:12px; }
    .logo { width:56px; height:56px; border-radius:6px; object-fit:cover; }
    .brand { font-weight:700; font-size:18px; color:#fff; text-decoration:none; }
    .content { padding:20px; color:#333; font-size:15px; line-height:1.5; }
    .details { background:#f8f9fb; padding:12px; border-radius:6px; margin:12px 0; }
    .footer { background:#f1f3f5; text-align:center; padding:12px; font-size:12px; color:#666; }
    .muted { color:#666; font-size:13px; }
    .strong { font-weight:700; }
    .btn { display:inline-block; padding:10px 16px; background:#0b5ed7; color:#fff; text-decoration:none; border-radius:6px; }
    @media only screen and (max-width:480px) {
      .brand { font-size:16px; }
      .content { padding:16px; font-size:14px; }
    }
  </style>
</head>
<body>
  @php
    use Carbon\Carbon;

    // Small helper to coerce numeric-like values to float
    $toFloat = fn($v) => is_numeric($v) ? (float) $v : (float) preg_replace('/[^\d.-]/', '', (string) $v);

    // Ensure $student exists
    $student = $student ?? null;

    // Normalize payments collection: prefer $payments, else build from single $payment, else empty collection
    if (!isset($payments) || !($payments instanceof \Illuminate\Support\Collection)) {
        if (isset($payment) && $payment) {
            $payments = collect([$payment]);
        } else {
            $payments = $student && method_exists($student, 'payments') ? ($student->payments ?? collect()) : collect();
        }
    }

    // If a single $payment variable exists but payments collection is present, prefer the collection for totals
    if (!isset($payment) && $payments->count() > 0) {
        $payment = $payments->sortByDesc('paid_at')->first();
    }

    // Resolve plan if not provided
    if (!isset($plan) || !$plan) {
        $plan = isset($student) ? \App\Models\Plan::where('key', $student->plan_key)->first() : null;
    }

    // Exchange rate service (may not be available in mail context)
    $rates = app()->has(\App\Services\ExchangeRateService::class)
        ? app(\App\Services\ExchangeRateService::class)
        : null;

    // Format helpers
    $formatDate = fn($d) => $d ? Carbon::parse($d)->format('d M Y H:i') : '—';
    $formatMoney = fn($v, $dec = 2) => number_format((float) $v, $dec);

    // Compute authoritative plan price in UGX
    // Priority:
    // 1) If student.course_fee exists and looks like UGX (heuristic), use it.
    // 2) If plan exists and is USD and we have rates, convert plan->price to UGX.
    // 3) If plan exists and is UGX, use plan->price.
    // 4) Fallback to numeric student.course_fee or 0.
    $planPriceUGX = 0.0;
    $studentCourseFee = isset($student->course_fee) ? $toFloat($student->course_fee) : null;

    if (!empty($studentCourseFee) && $studentCourseFee > 1000) {
        // Heuristic: values > 1000 are likely UGX already
        $planPriceUGX = $studentCourseFee;
    } elseif ($plan) {
        $planCurrency = strtoupper($plan->currency ?? 'UGX');
        $planPriceRaw = $toFloat($plan->price ?? 0);

        if ($planCurrency === 'USD' && $rates) {
            $planPriceUGX = (float) $rates->usdToUgx($planPriceRaw);
        } elseif ($planCurrency === 'UGX') {
            $planPriceUGX = $planPriceRaw;
        } else {
            // No rates available and student.course_fee not reliable: fallback to plan price (best-effort)
            $planPriceUGX = $planPriceRaw;
        }
    } else {
        $planPriceUGX = is_numeric($studentCourseFee) ? (float) $studentCourseFee : 0.0;
    }

    // Compute total paid in UGX: prefer amount_converted, otherwise convert USD payments if rates available
    $totalPaidUGX = 0.0;
    foreach ($payments as $p) {
        if (!is_null($p->amount_converted) && is_numeric($p->amount_converted) && $toFloat($p->amount_converted) > 0) {
            $totalPaidUGX += $toFloat($p->amount_converted);
            continue;
        }
        $pCurrency = strtoupper($p->currency ?? 'UGX');
        $pAmount = $toFloat($p->amount ?? 0);
        if ($pCurrency === 'USD' && $rates) {
            $totalPaidUGX += (float) $rates->usdToUgx($pAmount);
        } else {
            $totalPaidUGX += $pAmount;
        }
    }

    // Outstanding / due in UGX (ensure non-negative)
    $outstandingUgx = max(0.0, $planPriceUGX - $totalPaidUGX);

    // Receipt metadata
    $receiptNumber = ($payments->first()->receipt_number ?? null) ?? ($receipt->number ?? null);
    $issuedAt = ($payments->first()->paid_at ?? null) ?? ($receipt->issued_at ?? now());
  @endphp

  <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
      <td align="center" style="padding:20px;">
        <table class="container" width="100%" cellpadding="0" cellspacing="0" role="presentation">
          <tr>
            <td class="header">
              <div style="flex:1;">
               <div> <a href="{{ url('/') }}" class="brand" style="color:inherit;text-decoration:none;">SuperTrades Academy</a></div>
                <div style="font-size:12px;color:#e6f0ff;">Payment Verification</div>
              </div>
            </td>
          </tr>

          <tr>
            <td class="content">
              <p style="margin-top:0;">Hi {{ $student->first_name ?? ($student->name ?? 'Student') }},</p>

              <p>Thank you — we have recorded your payment. Below are the details:</p>

              <div class="details">
                <div><span class="strong">Receipt</span>: {{ $payment->receipt_number ?? $receiptNumber ?? 'N/A' }}</div>
                <div><span class="strong">Date</span>: {{ $formatDate($payment->paid_at ?? $issuedAt) }}</div>
                <div><span class="strong">Amount</span>: {{ number_format((float) ($payment->amount ?? 0), 2) }} {{ $payment->currency ?? 'UGX' }}</div>

                @if(!empty($payment->amount_converted))
                  <div><span class="strong">Amount (UGX)</span>: {{ number_format((float) $payment->amount_converted, 2) }} UGX</div>
                @elseif(isset($payment) && strtoupper($payment->currency ?? 'UGX') === 'USD' && $rates)
                  <div><span class="strong">Amount (UGX)</span>: {{ number_format((float) $rates->usdToUgx((float)$payment->amount), 2) }} UGX</div>
                @endif

                <div><span class="strong">Method</span>: {{ $payment->method ?? 'N/A' }}</div>
                {{-- <div><span class="strong">Notes</span>: {{ $payment->notes ?? '—' }}</div> --}}
              </div>

              {{-- <p class="muted">Outstanding balance: <strong>UGX {{ number_format((float) $outstandingUgx, 0) }}</strong></p> --}}

              @if(!empty($resetUrl))
                <p style="margin:18px 0 8px 0;">To set your password, click the button below:</p>
                <p style="text-align:center; margin:18px 0;">
                  <a href="{{ $resetUrl }}" class="btn" target="_blank" rel="noopener">Set Your Password</a>
                </p>
              @endif

              <p>If you believe this is incorrect or need a receipt in another format, reply to this email or contact support at
                 <a href="mailto:stapipsquad@gmail.com">stapipsquad@gmail.com</a>.
              </p>

              <p style="margin-bottom:0;">Thanks,<br><strong>The SuperTrades Academy Team</strong></p>
            </td>
          </tr>

          <tr>
            <td class="footer">
              <div style="margin-bottom:8px;">
                <a href="https://tiktok.com/@supertrade_sacademy?_r=1&_t=ZM-928R8YyzoYS" style="margin:0 6px; color:#666; text-decoration:none;">Tiktok</a> |
                <a href="https://www.instagram.com/supertrades_academy?igsh=ZnN2cGY4eDA2MjRn&utm_source=qr" style="margin:0 6px; color:#666; text-decoration:none;">Instagram</a>
              </div>
              &copy; {{ date('Y') }} SuperTrades Academy. All rights reserved.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>