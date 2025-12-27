{{-- resources/views/auth/passwords/reset.blade.php --}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Set a new password — Supertrades Academy</title>
  <style>
    :root{
      --bg:#f8fafc;
      --card:#ffffff;
      --brand-1:#0f172a;
      --brand-2:#4f46e5;
      --accent:#f59e0b;
      --muted:#64748b;
      --danger-bg:#fff1f2;
      --danger-border:#fee2e2;
      --success-bg:#ecfdf5;
      --success-border:#d1fae5;
      --text:#0f172a;
      --radius:12px;
      --max-width:720px;
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    html,body{height:100%;margin:0;background:var(--bg);color:var(--text);-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
    .page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:28px;}
    .wrap{width:100%;max-width:var(--max-width);padding:16px;box-sizing:border-box}
    .card{background:var(--card);border-radius:var(--radius);box-shadow:0 10px 30px rgba(15,23,42,0.08);overflow:hidden}
    .header{display:flex;align-items:center;gap:12px;padding:18px;background:linear-gradient(135deg,var(--brand-1) 0%,var(--brand-2) 100%);color:#fff}
    .logo{width:48px;height:48px;border-radius:10px;object-fit:cover;background:rgba(255,255,255,0.06);display:inline-block}
    .brand-title{font-weight:700;font-size:18px;line-height:1}
    .brand-sub{font-size:12px;opacity:.95;margin-top:2px}

    .body{padding:22px}
    h1{margin:0 0 8px 0;font-size:20px}
    p.lead{margin:0 0 18px 0;color:#475569;font-size:14px;line-height:1.45}
    .status{margin-bottom:12px;padding:10px 12px;background:var(--success-bg);border:1px solid var(--success-border);color:#065f46;border-radius:10px;font-size:13px}
    .errors{margin-bottom:12px;padding:10px 12px;background:var(--danger-bg);border:1px solid var(--danger-border);color:#991b1b;border-radius:10px;font-size:13px}
    form{display:block}
    label{display:block;font-size:13px;color:var(--text);margin-bottom:6px}
    input[type="email"], input[type="password"]{width:100%;padding:12px 14px;border:1px solid #e6e9ef;border-radius:10px;font-size:15px;color:var(--text);box-sizing:border-box}
    .row{margin-bottom:12px}
    .actions{display:flex;gap:12px;align-items:center;justify-content:flex-end;margin-top:8px;flex-wrap:wrap}
    button.primary{background:var(--accent);color:var(--brand-1);font-weight:700;padding:12px 16px;border-radius:10px;border:none;cursor:pointer;font-size:15px}
    .meta{margin-top:14px;display:flex;justify-content:space-between;align-items:center;font-size:13px;color:var(--muted);flex-wrap:wrap;gap:12px}
    .footer{padding:14px 18px;background:#fbfdff;color:var(--muted);font-size:13px;text-align:center}
    a.link{color:var(--accent);text-decoration:none}
    .hint{font-size:13px;color:var(--muted);margin-top:8px}

    @media (max-width:520px){
      .header{padding:14px}
      .body{padding:16px}
      h1{font-size:18px}
      input[type="email"], input[type="password"]{padding:10px 12px}
      button.primary{padding:10px 12px;font-size:14px}
      .actions{justify-content:center}
    }

    input:focus, button:focus { outline: 3px solid rgba(245,158,11,0.18); outline-offset:2px; }
  </style>
</head>
<body>
  <div class="page">
    <div class="wrap">
      <div class="card" role="main" aria-labelledby="reset-heading" style="max-width:640px;margin:0 auto;">
        <div class="header" role="banner">
          <img src="{{ asset('images/logo2.jfif') }}" alt="Supertrades Academy logo" class="logo" />
          <div>
            <div class="brand-title">Supertrades Academy</div>
            <div class="brand-sub">Set a new password</div>
          </div>
        </div>

        <div class="body" role="region" aria-labelledby="reset-heading">
          <h1 id="reset-heading">Set a new password</h1>
          <p class="lead">Choose a strong password to secure your account. We recommend at least 8 characters with mixed case and numbers.</p>

          {{-- Status message --}}
          @if (session('status'))
            <div class="status" role="status" aria-live="polite">
              {{ session('status') }}
            </div>
          @endif

          {{-- Validation errors --}}
          @if ($errors->any())
            <div class="errors" role="alert">
              <ul style="margin:0;padding-left:18px;">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form method="POST" action="{{ route('password.store') }}" novalidate>
            @csrf

            {{-- Password Reset Token --}}
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            {{-- Email Address --}}
            <div class="row">
              <label for="email">Email</label>
              <input id="email" name="email" type="email" value="{{ old('email', request()->email) }}" required autofocus autocomplete="username" />
            </div>

            {{-- Password --}}
            <div class="row">
              <label for="password">Password</label>
              <input id="password" name="password" type="password" required autocomplete="new-password" />
              <div class="hint">Use a strong password. At least 8 characters, include numbers and mixed case for best security.</div>
            </div>

            {{-- Confirm Password --}}
            <div class="row">
              <label for="password_confirmation">Confirm Password</label>
              <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
            </div>

            <div class="actions">
              <a href="{{ route('login') }}" class="link" style="margin-right:auto; align-self:center;">Back to sign in</a>
              <button type="submit" class="primary" aria-label="Reset Password">Reset Password</button>
            </div>
          </form>

          <div class="meta" aria-hidden="true">
            <span class="small">Reset links expire after {{ config('auth.passwords.users.expire', 60) }} minutes.</span>
            <span class="small">If you didn't request this, you can safely ignore this message.</span>
          </div>
        </div>

        <div class="footer" role="contentinfo">
          © {{ date('Y') }} Supertrades Academy — 
          {{-- <a href="mailto:stapipsquad@gmail.com" class="link">support@supertrades.academy</a> --}}
        </div>
      </div>
    </div>
  </div>
</body>
</html>
