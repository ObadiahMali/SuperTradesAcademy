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
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
      <td align="center" style="padding:20px;">
        <table class="container" width="100%" cellpadding="0" cellspacing="0" role="presentation">
          <tr>
            <td class="header">
              {{-- @if(!empty($logoCid))
                <img src="{{ asset('images/logo.png') }}" alt="SuperTrades Academy" class="logo">
              @else
                <div style="width:56px;height:56px;border-radius:6px;background:#fff;display:inline-block;"></div>
              @endif --}}
              <div style="flex:1;">
               <div> <a href="{{ url('/') }}" class="brand" style="color:inherit;text-decoration:none;">SuperTrades Academy</a></div>
                <div style="font-size:12px;color:#e6f0ff;">Payment Receipt</div>
              </div>
              <div style="text-align:right;color:#e6f0ff;font-size:13px;">{{ $student->first_name ?? 'Student' }}</div>
            </td>
          </tr>

          <tr>
            <td class="content">
              <p style="margin-top:0;">Hi {{ $student->first_name ?? ($student->name ?? 'Student') }},</p>

              <p>Thank you — we have recorded your payment. Below are the details:</p>

              <div class="details">
                <div><span class="strong">Receipt</span>: {{ $payment->receipt_number ?? 'N/A' }}</div>
                <div><span class="strong">Date</span>: {{ optional($payment->paid_at)->toDayDateTimeString() ?? now()->toDayDateTimeString() }}</div>
                <div><span class="strong">Amount</span>: {{ number_format((float) ($payment->amount ?? 0), 2) }} {{ $payment->currency ?? 'UGX' }}</div>
                @if(!empty($payment->amount_converted))
                  <div><span class="strong">Amount (UGX)</span>: {{ number_format((float) $payment->amount_converted, 2) }} UGX</div>
                @endif
                <div><span class="strong">Method</span>: {{ $payment->method ?? 'N/A' }}</div>
                <div><span class="strong">Notes</span>: {{ $payment->notes ?? '—' }}</div>
              </div>

              <p class="muted">Outstanding balance: <strong>{{ number_format((float) ($outstandingUgx ?? 0), 2) }} UGX</strong></p>

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
                {{-- <a href="https://facebook.com" style="margin:0 6px; color:#666; text-decoration:none;">Facebook</a> | --}}
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