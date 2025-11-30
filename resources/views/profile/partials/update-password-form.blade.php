{{-- styled update password section --}}
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

    /* Error text */
    .error { color:#b91c1c; font-size:13px; margin-top:6px; }

    /* Responsive */
    @media (max-width:520px) {
      .action-row { flex-direction:column-reverse; align-items:stretch; }
      .btn { width:100%; }
      .status-msg { margin-left:0; margin-bottom:6px; }
    }
  </style>

  <header>
    <h2>{{ __('Update Password') }}</h2>
    <p class="lead">{{ __('Ensure your account is using a long, random password to stay secure.') }}</p>
  </header>

  <form method="post" action="{{ route('password.update') }}" class="profile-form" novalidate>
    @csrf
    @method('put')

    <div class="form-row">
      <label class="form-label" for="update_password_current_password">{{ __('Current Password') }}</label>
      <input id="update_password_current_password" name="current_password" type="password" class="form-input" autocomplete="current-password">
      @if($errors->updatePassword && $errors->updatePassword->has('current_password'))
        <div class="error">{{ $errors->updatePassword->first('current_password') }}</div>
      @endif
    </div>

    <div class="form-row">
      <label class="form-label" for="update_password_password">{{ __('New Password') }}</label>
      <input id="update_password_password" name="password" type="password" class="form-input" autocomplete="new-password">
      @if($errors->updatePassword && $errors->updatePassword->has('password'))
        <div class="error">{{ $errors->updatePassword->first('password') }}</div>
      @endif
    </div>

    <div class="form-row">
      <label class="form-label" for="update_password_password_confirmation">{{ __('Confirm Password') }}</label>
      <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-input" autocomplete="new-password">
      @if($errors->updatePassword && $errors->updatePassword->has('password_confirmation'))
        <div class="error">{{ $errors->updatePassword->first('password_confirmation') }}</div>
      @endif
    </div>

    <div class="action-row">
      {{-- Visible Save button styled as primary --}}
      <button type="submit" class="btn btn-primary">Save</button>

      {{-- Saved status message --}}
      @if (session('status') === 'password-updated')
        <div class="status-msg" role="status">{{ __('Saved.') }}</div>
      @endif
    </div>
  </form>
</section>