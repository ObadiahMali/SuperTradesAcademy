{{-- resources/views/admin/users/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Create User')

@section('content')
<div class="container">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0">Create User</h4>
          <a href="{{ route('admin.users.create') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>

        @if(session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form method="POST" action="{{ route('admin.users.store') }}" novalidate>
          @csrf

          <div class="mb-3">
            <label class="form-label">Full name</label>
            <input name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select @error('role') is-invalid @enderror" required>
              @if(!empty($roles) && is_array($roles))
                @foreach($roles as $key => $label)
                  <option value="{{ $key }}" @selected(old('role') == $key)>{{ $label }}</option>
                @endforeach
              @else
                <option value="administrator" @selected(old('role') == 'administrator')>Administrator</option>
                <option value="secretary" @selected(old('role') == 'secretary')>Secretary</option>
              @endif
            </select>
            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Password</label>
              <div class="input-group">
                <input name="password" id="password" type="password" class="form-control @error('password') is-invalid @enderror" placeholder="Leave blank to auto-generate">
                <button type="button" class="btn btn-outline-secondary" id="togglePassword" title="Show or hide password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">Confirm Password</label>
              <input name="password_confirmation" type="password" class="form-control" placeholder="Repeat password">
            </div>
          </div>

          <div class="form-check form-switch mt-3">
            <input class="form-check-input" type="checkbox" id="send_invite" name="send_invite" value="1" @checked(old('send_invite'))>
            <label class="form-check-label" for="send_invite">Send welcome email with credentials</label>
          </div>

          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', true))>
            <label class="form-check-label" for="is_active">Active</label>
          </div>

          <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Create User</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Back to users</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  (function () {
    const toggle = document.getElementById('togglePassword');
    const pwd = document.getElementById('password');
    if (!toggle || !pwd) return;
    toggle.addEventListener('click', function () {
      const type = pwd.getAttribute('type') === 'password' ? 'text' : 'password';
      pwd.setAttribute('type', type);
      this.querySelector('i').classList.toggle('bi-eye');
      this.querySelector('i').classList.toggle('bi-eye-slash');
    });
  })();
</script>
@endpush

@endsection