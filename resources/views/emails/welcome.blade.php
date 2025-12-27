<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to SuperTrades Academy</title>
</head>
<body style="font-family: Arial, sans-serif; background-color:#f9f9f9; padding:20px;">
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
                    <li>Intake: {{ $student->intake->name ?? 'N/A' }}</li>
                    <li>Plan: {{ $student->plan_key ?? 'N/A' }}</li>
                    <li>Email: {{ $student->email ?? 'N/A' }}</li>
                    <li>Phone: {{ $student->phone_display ?? 'N/A' }}</li>
                    {{-- <li>Price: {{ $originalDisplay }} </li> --}}

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