<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to SuperTrades Academy</title>
</head>
<body style="font-family: Arial, sans-serif; background-color:#f9f9f9; padding:20px;">
    @php
        use Carbon\Carbon;

        // Ensure $student exists
        $student = $student ?? null;

        // Resolve plan if not provided
        if (!isset($plan) || !$plan) {
            $plan = $student ? \App\Models\Plan::where('key', $student->plan_key)->first() : null;
        }

        // Exchange rate service (optional)
        $rates = app()->has(\App\Services\ExchangeRateService::class)
            ? app(\App\Services\ExchangeRateService::class)
            : null;

        // Compute originalDisplay (prefer plan, fallback to student.course_fee)
        if (!isset($originalDisplay)) {
            if ($plan) {
                $pc = strtoupper($plan->currency ?? 'UGX');
                $pp = (float) ($plan->price ?? 0);
                $originalDisplay = $pc === 'UGX'
                    ? "{$pc} " . number_format($pp, 0)
                    : "{$pc} " . number_format($pp, 2);
            } else {
                $originalDisplay = 'UGX ' . number_format($student->course_fee ?? 0, 2);
            }
        }

        // Compute plan price in UGX for display and calculations
        if (!isset($planPriceUGX)) {
            if ($plan) {
                $pc = strtoupper($plan->currency ?? 'UGX');
                $pp = (float) ($plan->price ?? 0);
                if ($pc === 'USD' && $rates) {
                    $planPriceUGX = (float) $rates->usdToUgx($pp);
                } elseif ($pc === 'USD' && !$rates) {
                    // Defensive fallback: treat stored price as UGX if no rate service
                    $planPriceUGX = $pp;
                } else {
                    $planPriceUGX = (float) $pp;
                }
            } else {
                $planPriceUGX = is_numeric($student->course_fee ?? null) ? (float) $student->course_fee : 0.0;
            }
        }

        // Determine payments collection and compute totalPaidUGX
        $payments = $payments ?? ($student && method_exists($student, 'payments') ? $student->payments : collect());
        if (!isset($totalPaidUGX)) {
            $totalPaidUGX = 0.0;
            if ($payments instanceof \Illuminate\Support\Collection) {
                foreach ($payments as $p) {
                    if (!is_null($p->amount_converted) && is_numeric($p->amount_converted) && (float)$p->amount_converted > 0) {
                        $totalPaidUGX += (float) $p->amount_converted;
                        continue;
                    }
                    $pCurrency = strtoupper($p->currency ?? 'UGX');
                    $pAmount = (float) ($p->amount ?? 0);
                    if ($pCurrency === 'USD' && $rates) {
                        $totalPaidUGX += (float) $rates->usdToUgx($pAmount);
                    } else {
                        $totalPaidUGX += $pAmount;
                    }
                }
            }
        }

        // Outstanding balance in UGX
        $outstandingUgx = $outstandingUgx ?? max(0.0, $planPriceUGX - $totalPaidUGX);

        // Phone display fallback
        $phoneDisplay = $phoneDisplay ?? ($student->phone_display ?? null);

        // Reset URL fallback (if provided by controller)
        $resetUrl = $resetUrl ?? null;

        // Format helpers
        $formatMoney = fn($v, $dec = 2) => number_format((float) $v, $dec);
    @endphp

    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; margin:auto; background:#fff; border-radius:8px; overflow:hidden;">
        <tr>
            <td style="background:#007bff; color:#fff; padding:20px; text-align:center;">
                <h1 style="margin:0;">SuperTrades Academy</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:20px;">
                <p>Dear {{ $student->first_name ?? ($student->name ?? 'Student') }},</p>

                <p>Welcome to <strong>SuperTrades Academy</strong>! We’re thrilled to have you join our community of learners.</p>

                <p>Your enrollment details:</p>
                <ul>
                    <li>Intake: {{ optional($student->intake)->name ?? 'N/A' }}</li>
                    <li>Plan: {{ $plan->label ?? $student->plan_key ?? 'N/A' }}</li>
                    <li>Email: {{ $student->email ?? 'N/A' }}</li>
                    <li>Phone: {{ $phoneDisplay ?? 'N/A' }}</li>
                    <li>
                        <div style="margin-top:6px;">
                            @php
                                // Prefer plan original display; fallback already computed in $originalDisplay
                                $orig = $originalDisplay;
                            @endphp

                            <strong>Price:</strong> {{ $orig }}

                          
                        </div>
                    </li>
                </ul>

                @if(!empty($resetUrl))
                    <p>To set your password, click the button below:</p>
                    <p style="text-align:center; margin:30px 0;">
                        <a href="{{ $resetUrl }}"
                           style="display:inline-block; padding:12px 24px; background:#007bff; color:#fff; text-decoration:none; border-radius:4px;">
                           Set Your Password
                        </a>
                    </p>
                @endif

                <p>We look forward to supporting your learning journey. You’ll receive updates, mentorship schedules, and receipts through this email address.</p>

                <p>If you need help, contact support at
                   <a href="mailto:stapipsquad@gmail.com" style="color:#0b5ed7; text-decoration:none;">stapipsquad@gmail.com</a>.
                </p>

                <p>Welcome aboard,<br>
                The SuperTrades Academy Team</p>
            </td>
        </tr>
        <tr>
            <td style="background:#f1f1f1; text-align:center; padding:10px; font-size:12px; color:#555;">
                &copy; {{ date('Y') }} SuperTrades Academy. All rights reserved.
            </td>
        </tr>
    </table>
</body>
</html>