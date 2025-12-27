<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ config('app.name', 'Supertrades Academy') }} - Register</title>
    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-slate-50 font-sans text-slate-800">
  <div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-3xl grid grid-cols-1 md:grid-cols-2 gap-8">

      <!-- Left: Branding / Logo (same as your sign-in page) -->
      <div class="hidden md:flex flex-col justify-center items-start bg-gradient-to-br from-slate-900 to-indigo-800 text-white rounded-lg shadow-lg p-10">
        <div class="flex items-center gap-4 mb-6">
          <div class="w-16 h-16 bg-white/10 rounded-full flex items-center justify-center">
            <img src="{{ asset('images/logo2.jfif') }}" alt="Supertrades Academy logo" class="w-12 h-12 object-contain" />
          </div>
          <div>
            <h2 class="text-2xl font-semibold tracking-tight">Supertrades Academy</h2>
            <p class="text-sm text-slate-200/90">Practical mentorship · Market-ready skills</p>
          </div>
        </div>

        <div class="space-y-4">
          <h3 class="text-lg font-medium">Create an account</h3>
          <p class="text-sm text-slate-200/80 leading-relaxed">
            Create a new user for the platform. Administrators can set roles and send an invite link.
          </p>

          <ul class="mt-4 space-y-2 text-sm">
            <li class="flex items-start gap-3">
              <span class="inline-flex items-center justify-center w-6 h-6 bg-amber-400 text-slate-900 rounded-full text-xs font-semibold">✓</span>
              <span>Secure onboarding</span>
            </li>
            <li class="flex items-start gap-3">
              <span class="inline-flex items-center justify-center w-6 h-6 bg-amber-400 text-slate-900 rounded-full text-xs font-semibold">✓</span>
              <span>Role assignment</span>
            </li>
            <li class="flex items-start gap-3">
              <span class="inline-flex items-center justify-center w-6 h-6 bg-amber-400 text-slate-900 rounded-full text-xs font-semibold">✓</span>
              <span>Password reset invites</span>
            </li>
          </ul>
        </div>

        <div class="mt-auto text-sm text-slate-300">
          <p>Need help? Contact <a href="mailto:support@supertrades.academy" class="underline text-slate-100">support@supertrades.academy</a></p>
        </div>
      </div>

      <!-- Right: Register card -->
      <div class="bg-white rounded-lg shadow-md p-8 flex flex-col justify-center">
        <div class="flex items-center justify-center md:justify-start mb-6">
          <img src="{{ asset('images/logo2.jfif') }}" alt="Supertrades Academy" class="w-12 h-12 object-contain mr-3" />
          <div>
            <h1 class="text-2xl font-bold leading-tight">Create account</h1>
            <p class="text-sm text-slate-500">Fill the form to register a new user</p>
          </div>
        </div>

        <form method="POST"
              action="{{ auth()->check() && auth()->user()->can('manage-users') ? route('admin.register.store') : route('register') }}"
              class="space-y-4">
            @csrf

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                       class="mt-1 block w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400" />
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       class="mt-1 block w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400" />
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <!-- Role selector (admins only) -->
            {{-- @can('manage-users') --}}
            <div>
                <label for="role" class="block text-sm font-medium text-slate-700">Role</label>
                <select id="role" name="role"
                        class="mt-1 block w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <option value="secretary" {{ old('role') === 'secretary' ? 'selected' : '' }}>Secretary</option>
                    <option value="administrator" {{ old('role') === 'administrator' ? 'selected' : '' }}>Administrator</option>
                </select>
                @error('role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-2">
                <input id="send_invite" type="checkbox" name="send_invite" value="1" class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400" {{ old('send_invite') ? 'checked' : '' }}>
                <label for="send_invite" class="text-sm text-slate-600">Send password reset link</label>
            </div>
            {{-- @endcan --}}

            <!-- Password (optional for admin-created users) -->
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <input id="password" name="password" type="password"
                       @if(!(auth()->check() && auth()->user()->can('manage-users'))) required @endif
                       class="mt-1 block w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400" />
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @if(auth()->check() && auth()->user()->can('manage-users'))
                    <p class="text-xs text-slate-500 mt-1">Leave blank to generate a secure password and optionally send a reset link.</p>
                @endif
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       @if(!(auth()->check() && auth()->user()->can('manage-users'))) required @endif
                       class="mt-1 block w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400" />
                @error('password_confirmation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('login') }}" class="text-sm text-amber-600 hover:underline">Already registered?</a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-400">
                    Register
                </button>
            </div>
        </form>

        <div class="mt-6 text-center text-xs text-slate-400">
          <p>© {{ date('Y') }} Supertrades Academy. All rights reserved.</p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
