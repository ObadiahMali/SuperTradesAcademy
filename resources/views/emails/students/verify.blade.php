<p>Hello {{ $student->first_name }},</p>
<p>Please verify your email by clicking the link below:</p>
<p><a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a></p>
<p>Or use this code: <strong>{{ $token }}</strong></p>