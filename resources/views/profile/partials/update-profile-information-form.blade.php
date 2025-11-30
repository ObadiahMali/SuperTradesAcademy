{{-- styled profile information section --}}
<section class="profile-card">
  <style>
    /* Scoped styles for this section */
    .profile-card { background:#fff; border-radius:10px; box-shadow:0 8px 24px rgba(15,23,42,0.06); padding:20px; margin-bottom:16px; }
    .profile-card header { margin-bottom:14px; }
    .profile-card h2 { font-size:18px; margin:0 0 6px 0; color:#0f172a; font-weight:600; }
    .profile-card p.lead { margin:0; color:#475569; font-size:13px; }

    /* Form layout */
    .profile-form { margin-top:14px; display:block; gap:12px; }
    .form-row { margin-bottom:12px; }
    .form-label { display:block; font-size:13px; color:#0f172a; margin-bottom:6px; font-weight:500; }
    .form-input, .form-select, .form-textarea {
      width:100%; padding:10px 12px; border:1px solid #e6e9ef; border-radius:8px; font-size:14px; color:#0f172a;
      background:#fff; box-sizing:border-box;
    }
    .form-textarea { min-height:100px; resize:vertical; }

    /* Action row */
    .action-row { display:flex; gap:10px; align-items:center; justify-content:flex-end; margin-top:12px; }
    .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:9px 14px; border-radius:8px; font-weight:600; cursor:pointer; border:1px solid transparent; text-decoration:none; }
    .btn-primary { background:#2563eb; color:#fff; border-color:#2563eb; }
    .btn-primary:hover { background:#1e4fd8; }
    .status-msg { margin-left:8px; font-size:13px; color:#065f46; background:#ecfdf5; padding:6px 8px; border-radius:6px; }

    /* Small / helper text */
    .muted { color:#6b7280; font-size:13px; }
    .error { color:#b91c1c; font-size:13px; margin-top:6px; }

    /* Responsive */
    @media (max-width:520px) {
      .action-row { flex-direction:column-reverse; align-items:stretch; }
      .btn { width:100%; }
      .status-msg { margin-left:0; margin-bottom:6px; }
    }
  </style>

  <header>
    <h2>{{ __('Profile Information') }}</h2>
    <p class="lead">{{ __("Update your account's profile information and email address.") }}</p>
  </header>

  <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

  <form method="post" action="{{ route('profile.update') }}" class="profile-form" novalidate>
    @csrf
    @method('patch')

    <div class="form-row">
      <label class="form-label" for="name">{{ __('Name') }}</label>
      <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
      @error('name') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="form-row">
      <label class="form-label" for="email">{{ __('Email') }}</label>
      <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $user->email) }}" required autocomplete="username">
      @error('email') <div class="error">{{ $message }}</div> @enderror

      @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
        <div style="margin-top:10px;">
          <p class="muted">
            {{ __('Your email address is unverified.') }}
            <button form="send-verification" type="submit" class="btn" style="background:transparent;color:#2563eb;border:0;padding:0 6px;font-weight:600;">
              {{ __('Click here to re-send the verification email.') }}
            </button>
          </p>

          @if (session('status') === 'verification-link-sent')
            <p class="status-msg">{{ __('A new verification link has been sent to your email address.') }}</p>
          @endif
        </div>
      @endif
    </div>

    <div class="action-row">
      {{-- Visible Save button styled as primary --}}
      <button type="submit" class="btn btn-primary">Save</button>

      {{-- Saved status message --}}
      @if (session('status') === 'profile-updated')
        <div class="status-msg" role="status">{{ __('Saved.') }}</div>
      @endif
    </div>
  </form>
</section>