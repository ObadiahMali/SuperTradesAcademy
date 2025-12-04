<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>{{ config('app.name', 'Supertrades Academy') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-slate-50 font-sans text-slate-800">
  <div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-3xl grid grid-cols-1 md:grid-cols-2 gap-8">

      <!-- Left: Branding / Logo -->
      <div class="hidden md:flex flex-col justify-center items-start bg-gradient-to-br from-slate-900 to-indigo-800 text-white rounded-lg shadow-lg p-10">
        <!-- Logo area -->
        <div class="flex items-center gap-4 mb-6">
          <!-- Replace src with your logo path -->
          <div class="w-16 h-16 bg-white/10 rounded-full flex items-center justify-center">
            <img src="{{ asset('images/logo2.jfif') }}" alt="Supertrades Academy logo" class="w-12 h-12 object-contain" />
          </div>
          <div>
            <h2 class="text-2xl font-semibold tracking-tight">Supertrades Academy</h2>
            <p class="text-sm text-slate-200/90">Practical mentorship · Market-ready skills</p>
          </div>
        </div>

        <!-- Tagline and benefits -->
        <div class="space-y-4">
          <h3 class="text-lg font-medium">Welcome back</h3>
          <p class="text-sm text-slate-200/80 leading-relaxed">
            Sign in to access your dashboard, track mentorship progress, and manage student payments.
            Supertrades Academy helps you build real trading skills with guided mentorship and practical projects.
          </p>

          <ul class="mt-4 space-y-2 text-sm">
            <li class="flex items-start gap-3">
              <span class="inline-flex items-center justify-center w-6 h-6 bg-amber-400 text-slate-900 rounded-full text-xs font-semibold">✓</span>
              <span>Structured mentorship plans</span>
            </li>
            <li class="flex items-start gap-3">
              <span class="inline-flex items-center justify-center w-6 h-6 bg-amber-400 text-slate-900 rounded-full text-xs font-semibold">✓</span>
              <span>Secure payments and receipts</span>
            </li>
            <li class="flex items-start gap-3">
              <span class="inline-flex items-center justify-center w-6 h-6 bg-amber-400 text-slate-900 rounded-full text-xs font-semibold">✓</span>
              <span>Progress tracking and reports</span>
            </li>
          </ul>
        </div>

        <div class="mt-auto text-sm text-slate-300">
          <p>Need help? Contact support at <a href="mailto:support@supertrades.academy" class="underline text-slate-100">support@supertrades.academy</a></p>
        </div>
      </div>

      <!-- Right: Auth card -->
      <div class="bg-white rounded-lg shadow-md p-8 flex flex-col justify-center">
        <div class="flex items-center justify-center md:justify-start mb-6">
          <!-- Small logo for auth card -->
          <img src="{{ asset('images/logo2.jfif') }}" alt="Supertrades Academy" class="w-12 h-12 object-contain mr-3" />
          <div>
            <h1 class="text-2xl font-bold leading-tight">Sign in</h1>
            <p class="text-sm text-slate-500">Enter your credentials to continue</p>
          </div>
        </div>

        @guest
          <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
              <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
              <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                     class="mt-1 block w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400" />
              @error('email')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
              @enderror
            </div>

            <div>
              <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
              <input id="password" name="password" type="password" required
                     class="mt-1 block w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400" />
              @error('password')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
              @enderror
            </div>

            <div class="flex items-center justify-between">
              <label class="flex items-center text-sm">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400" />
                <span class="ml-2 text-slate-600">Remember me</span>
              </label>

              <a href="{{ route('password.request') }}" class="text-sm text-amber-600 hover:underline">Forgot password?</a>
            </div>

            <div>
              <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-900 shadow hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-400">
                <!-- Icon (optional) -->
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11a4 4 0 100-8 4 4 0 000 8z" />
                </svg>
                Sign in
              </button>
            </div>
          </form>
        @else
          <div class="text-center">
            <p class="text-slate-700 mb-4">Welcome back, <span class="font-semibold">{{ auth()->user()->name }}</span></p>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="w-full rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Logout</button>
            </form>
            <a href="{{ route('dashboard') }}" class="mt-4 inline-block text-sm text-amber-600 hover:underline">Go to Dashboard</a>
          </div>
        @endguest

        <div class="mt-6 text-center text-xs text-slate-400">
          <p>© {{ date('Y') }} Supertrades Academy. All rights reserved.</p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>