{{-- resources/views/emails/welcome-set-password.blade.php --}}
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Welcome to SuperTrades Academy</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <style>
    /* Email-safe, minimal styles */
    body { font-family: Arial, Helvetica, sans-serif; background-color: #f9f9f9; margin: 0; padding: 20px; color: #0f172a; }
    .email-wrap { max-width: 600px; margin: auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #eef2f7; }
    .preheader { display:none !important; visibility:hidden; mso-hide:all; font-size:1px; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden; }
    .header { background: #007bff; color: #fff; padding: 20px; text-align: center; }
    .header .brand { font-size: 20px; font-weight: 700; margin: 0; display: inline-block; vertical-align: middle; }
    .logo { height: 40px; width: auto; vertical-align: middle; border-radius: 6px; margin-right: 10px; }
    .content { padding: 20px; color: #0f172a; line-height: 1.5; }
    .muted { color: #6b7280; font-size: 13px; }
    .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 700; }
    .list { margin: 12px 0; padding-left: 18px; }
    .footer { background: #f1f1f1; text-align: center; padding: 10px; font-size: 12px; color: #555; }
    .fallback { word-break: break-all; color: #0f172a; text-decoration: none; }
    .meta { font-size: 13px; color: #6b7280; margin-top: 8px; }
    .label { font-weight:700; color:#0f172a; }
    @media (max-width: 480px) {
      .content { padding: 16px; }
      .header { padding: 16px; }
      .btn { padding: 10px 18px; }
    }
  </style>
</head>
<body>
  <span class="preheader">Welcome to SuperTrades Academy — set your password to access your account.</span>

  <div class="email-wrap" role="article" aria-label="Welcome email">
    {{-- <div class="header" role="banner">
      @if (file_exists(public_path('images/logo2.jfif')))
        <img src="{{ asset('images/logo2.jfif') }}" alt="SuperTrades Academy" class="logo" />
      @endif
      <span class="brand">SuperTrades Academy</span>
    </div> --}}

    <div class="content" role="main">
      @php
        // Prefer $user variable for admin-created accounts
        $recipient = $user ?? $recipient ?? null;
        $recipientName = $recipient->name ?? ($recipient->first_name ?? 'User');
        $recipientEmail = $recipient->email ?? null;
        $recipientRole = $recipient->role ?? ($role ?? null);
        $expiry = $expiryMinutes ?? config('auth.passwords.users.expire', 60);
      @endphp

      <p style="margin:0 0 12px 0;">Dear {{ $recipientName }},</p>

      <p style="margin:0 0 12px 0;">
        Welcome to <strong>SuperTrades Academy</strong>! We’re pleased to have you on board
        @if(!empty($recipientRole))
          as a <strong>{{ ucfirst($recipientRole) }}</strong>.
        @else
          as part of our team.
        @endif
      </p>

      @if(!empty($recipientEmail) || !empty($recipientRole))
        <p style="margin:0 0 8px 0;" class="label">Account details</p>
        <ul class="list" style="margin-bottom:12px;">
          @if(!empty($recipientRole)) <li><span class="label">Role:</span> {{ ucfirst($recipientRole) }}</li> @endif
          @if(!empty($recipientEmail)) <li><span class="label">Email:</span> {{ $recipientEmail }}</li> @endif
        </ul>
      @endif

      @if(!empty($resetUrl))
        <p style="margin:0 0 8px 0;">To set your password and access your account, click the button below:</p>

        <p style="text-align:center; margin:18px 0;">
          <a href="{{ $resetUrl }}" class="btn" target="_blank" rel="noopener" aria-label="Set your SuperTrades password">Set Your Password</a>
        </p>

        <p class="muted" style="margin:0 0 12px 0;">This password reset link will expire in {{ $expiry }} minutes.</p>
      @else
        <p class="muted" style="margin:0 0 12px 0;">If you need to set or reset your password, request a password reset from the sign in page.</p>
      @endif

      <p style="margin:0 0 12px 0;">
        We’ll send important updates and account notifications to this email address. If you need help, contact support at
        <a href="mailto:stapipsquad@gmail.com" style="color:#0b5ed7; text-decoration:none;">stapipsquad@gmail.com</a>.
      </p>

      <p style="margin:0 0 8px 0;">Regards,<br>The SuperTrades Academy Team</p>

      @if(!empty($resetUrl))
        <hr style="border:none;border-top:1px solid #eef2f7;margin:18px 0">
        <p class="muted" style="font-size:13px;margin:0 0 8px 0;">
          If you're having trouble clicking the "Set Your Password" button, copy and paste the URL below into your web browser:
        </p>
        <p style="font-size:13px;margin:8px 0 0 0;">
          <a class="fallback" href="{{ $resetUrl }}">{{ $resetUrl }}</a>
        </p>
      @endif
    </div>

    <div class="footer" role="contentinfo">
      &copy; {{ date('Y') }} SuperTrades Academy. All rights reserved.
    </div>
  </div>
</body>
</html>
