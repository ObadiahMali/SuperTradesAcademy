<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to SuperTrades Academy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <style>
        /* Basic responsive email styles */
        body { margin:0; padding:0; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; background:#f4f6f8; font-family: Arial, Helvetica, sans-serif; }
        table { border-collapse:collapse; }
        img { border:0; line-height:100%; outline:none; text-decoration:none; display:block; max-width:100%; height:auto; }
        .container { max-width:600px; margin:0 auto; background:#ffffff; border-radius:8px; overflow:hidden; }
        .header { background:#0b5ed7; color:#ffffff; padding:18px; text-align:left; }
        .logo { display:inline-block; vertical-align:middle; }
        .brand { display:inline-block; vertical-align:middle; margin-left:12px; font-size:20px; font-weight:700; color:#ffffff; }
        .content { padding:22px; color:#333333; font-size:15px; line-height:1.5; }
        .btn { display:inline-block; padding:12px 20px; background:#0b5ed7; color:#ffffff; text-decoration:none; border-radius:6px; }
        .details { background:#f8f9fb; padding:12px; border-radius:6px; margin:12px 0; }
        .footer { background:#f1f3f5; text-align:center; padding:14px; font-size:12px; color:#666666; }
        @media only screen and (max-width:480px) {
            .brand { font-size:18px; }
            .content { padding:16px; font-size:14px; }
        }
    </style>
</head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center" style="padding:20px;">
                <table class="container" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <!-- Header with logo -->
                    <tr>
                        <td class="header">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <a href="{{ url('/') }}" style="text-decoration:none; color:inherit;">
                                            {{-- <img src="{{ asset('images/logo.png') }}" alt="SuperTrades Academy" width="40" height="56" style="display:inline-block; border-radius:6px;" class="logo"> --}}
                                            {{-- <span class="brand">SuperTrades Academy</span> --}}
                                        </a>
                                    </td>
                                    <td style="text-align:right; vertical-align:middle; color:#e6f0ff; font-size:13px;">
                                        <span>Welcome aboard</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td class="content">
                            <p style="margin-top:0;">Dear {{ $student->first_name ?? ($student->name ?? 'Student') }},</p>

                            <p>Welcome to <strong>SuperTrades Academy</strong>! We’re thrilled to have you join our community of learners.</p>

                            <p>Your enrollment details:</p>

                            <div class="details">
                                <strong>Intake:</strong> {{ $student->intake->name ?? 'N/A' }}<br>
                                <strong>Plan:</strong> {{ $student->plan_key ?? 'N/A' }}<br>
                                <strong>Email:</strong> {{ $student->email ?? 'N/A' }}<br>
                                <strong>Phone:</strong> {{ $student->phone_display ?? 'N/A' }}
                            </div>

                            @if(!empty($resetUrl))
                                <p style="margin:18px 0 8px 0;">To set your password, click the button below:</p>
                                <p style="text-align:center; margin:18px 0;">
                                    <a href="{{ $resetUrl }}" class="btn" target="_blank" rel="noopener">Set Your Password</a>
                                </p>
                            @endif

                            <p>We look forward to supporting your learning journey. You’ll receive updates, mentorship schedules, and receipts through this email address.</p>

                            <p>If you need help, contact support at
                               <a href="mailto:stapipsquad@gmail.com" style="color:#0b5ed7; text-decoration:none;">stapipsquad@gmail.com</a>.
                            </p>

                            <p style="margin-bottom:0;">Welcome aboard,<br>
                            <strong>The SuperTrades Academy Team</strong></p>
                        </td>
                    </tr>

                    <!-- Footer -->
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