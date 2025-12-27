{{-- resources/views/profile/show.blade.php --}}
@extends('layouts.app')

@section('content')
<style>
  /* Container and grid */
  .profile-container {
    padding: 48px 16px;
    max-width: 1120px;
    margin: 0 auto;
    box-sizing: border-box;
  }

  .profile-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
  }

  @media (min-width: 992px) {
    .profile-grid {
      grid-template-columns: 320px 1fr;
      gap: 32px;
    }
  }

  /* Card */
  .card {
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 6px 18px rgba(20, 24, 40, 0.06);
    padding: 20px;
    box-sizing: border-box;
  }

  /* Profile summary */
  .profile-summary {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .profile-head {
    display: flex;
    gap: 14px;
    align-items: center;
  }

  .avatar {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: #f3f4f6;
    color: #374151;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 28px;
  }

  .profile-name {
    font-size: 18px;
    font-weight: 600;
    color: #111827;
    margin: 0;
  }

  .profile-email {
    font-size: 13px;
    color: #6b7280;
    margin: 2px 0 0 0;
  }

  .meta {
    font-size: 13px;
    color: #6b7280;
  }

  .profile-actions {
    display: flex;
    gap: 8px;
    margin-top: 8px;
  }

  .btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    text-decoration: none;
    border: 1px solid transparent;
  }

  .btn-primary {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
  }

  .btn-primary:hover { background: #1e4fd8; }

  .btn-outline {
    background: #fff;
    color: #374151;
    border-color: #d1d5db;
  }

  .btn-danger {
    background: #fff;
    color: #b91c1c;
    border-color: #fca5a5;
  }

  .info-block {
    border-top: 1px solid #eef2f7;
    padding-top: 12px;
    margin-top: 8px;
    font-size: 14px;
    color: #374151;
  }

  /* Right column sections */
  .section-title {
    font-size: 16px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 8px 0;
  }

  .section-sub {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 12px;
  }

  .section-body {
    display: block;
  }

  /* Debug badge */
  .debug-badge {
    margin-top: 12px;
    padding: 8px 10px;
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #d1fae5;
    border-radius: 6px;
    font-size: 13px;
  }

  /* Form spacing helpers */
  .form-row { margin-bottom: 12px; }
  .form-label { display:block; margin-bottom:6px; font-size:13px; color:#374151; }
  .form-input, .form-select, .form-textarea {
    width:100%;
    padding:8px 10px;
    border:1px solid #e6e9ef;
    border-radius:6px;
    font-size:14px;
    color:#111827;
    box-sizing:border-box;
  }

  /* Section/card styling */
  .card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 6px 18px rgba(20,24,40,0.06);
    padding: 20px;
    box-sizing: border-box;
    margin-bottom: 16px;
  }

  /* Title and subtitle */
  .section-title {
    font-size: 18px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 6px 0;
  }

  .section-sub {
    font-size: 13px;
    color: #6b7280;
    margin: 0 0 12px 0;
  }

  /* Body area */
  .section-body {
    padding-top: 6px;
    border-top: 1px solid #eef2f7;
  }

  /* Ensure form controls inside the partial are visible */
  .section-body .form-input,
  .section-body .form-select,
  .section-body .form-textarea,
  .section-body input,
  .section-body select,
  .section-body textarea {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #e6e9ef;
    border-radius: 6px;
    font-size: 14px;
    color: #111827;
    box-sizing: border-box;
  }

  /* Buttons inside the section */
  .section-body .form-actions,
  .section-body .actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 12px;
  }

  .section-body .btn {
    padding: 8px 14px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
  }

  /* Primary and danger styles */
  .section-body .btn-primary { background:#2563eb; color:#fff; border:1px solid #2563eb; }
  .section-body .btn-danger  { background:#dc2626; color:#fff; border:1px solid #b91c1c; }

  /* Danger button (solid red) */
  .btn-danger {
    background: #dc2626; /* red-600 */
    color: #ffffff;
    border: 1px solid #b91c1c; /* slightly darker border */
    padding: 8px 14px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  /* Hover and focus states for accessibility */
  .btn-danger:hover,
  .btn-danger:focus {
    background: #b91c1c; /* red-700 */
    border-color: #7f1d1d;
    outline: none;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
  }

  /* Outline variant (if you want a white background with red border) */
  .btn-danger-outline {
    background: #fff;
    color: #b91c1c;
    border: 1px solid #fca5a5;
  }

  /* Make sure inputs and selects don't hide buttons on small screens */
  @media (max-width: 480px) {
    .form-actions { flex-direction: column-reverse; align-items: stretch; }
    .btn { width: 100%; }
  }

  .form-textarea { min-height: 90px; resize:vertical; }

  /* Small text */
  .muted { color:#6b7280; font-size:13px; }

  /* Disabled look for non-interactive buttons */
  .disabled.no-pointer {
    pointer-events: none;
    opacity: 0.6;
    cursor: not-allowed;
  }
</style>

@php
  $user = auth()->user();
  // Safely get role names: use getRoleNames() if available, otherwise fall back to role column
  if ($user) {
      if (method_exists($user, 'getRoleNames')) {
          $roleDisplay = $user->getRoleNames()->isNotEmpty()
              ? $user->getRoleNames()->implode(', ')
              : ($user->role ?? '—');
      } else {
          $roleDisplay = $user->role ?? '—';
      }
  } else {
      $roleDisplay = '—';
  }
@endphp

<div class="profile-container">
  <div class="profile-grid">

    {{-- Left column: profile summary --}}
    <aside class="card profile-summary">
      <div class="profile-head">
        <div class="avatar" aria-hidden="true">
          {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
        </div>

        <div>
          <p class="profile-name">{{ $user->name ?? '—' }}</p>
          <p class="profile-email">{{ $user->email ?? '—' }}</p>
          <p class="meta">Member since {{ optional($user->created_at)->format('M Y') ?? '—' }}</p>
        </div>
      </div>

      <div class="info-block" role="region" aria-label="Profile information">
        <p class="muted"><strong>User ID</strong>: {{ auth()->id() ?? '—' }}</p>
        <p class="muted" style="margin-top:6px;"><strong>Role</strong>: {{ $roleDisplay }}</p>

        <div class="profile-actions" style="margin-top:12px;">
          <a href="{{ route('profile.edit') }}" class="btn btn-primary" title="Edit profile">Edit profile</a>

          <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Are you sure you want to delete your account?');" style="display:inline;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger" title="Delete account">Delete account</button>
          </form>
        </div>
      </div>

      <div class="debug-badge" aria-hidden="true">DEBUG: profile page loaded — user id {{ auth()->id() }}</div>
    </aside>

    {{-- Right column: forms --}}
    <main>
      {{-- Update profile information --}}
      <section class="card" style="margin-bottom:16px;">
        <h3 class="section-title">Profile information</h3>
        <p class="section-sub">Update your account’s profile information and email address.</p>

        <div class="section-body">
          @includeIf('profile.partials.update-profile-information-form')
        </div>
      </section>

      {{-- Update password --}}
      <section class="card" style="margin-bottom:16px;">
        <h3 class="section-title">Change password</h3>
        <p class="section-sub">Ensure your account is using a long, random password to stay secure.</p>

        <div class="section-body">
          @includeIf('profile.partials.update-password-form')
        </div>
      </section>

      {{-- Delete account --}}
      <section class="card">
        <h3 class="section-title">Danger zone</h3>
        <p class="section-sub">Permanently delete your account and all associated data.</p>

        <div class="section-body">
          @includeIf('profile.partials.delete-user-form')
        </div>
      </section>
    </main>

  </div>
</div>
@endsection
