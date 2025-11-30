<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-100 font-sans">
    <div class="min-h-screen flex flex-col items-center justify-center">
        <div class="w-full max-w-md bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold mb-4 text-center">{{ config('app.name', 'Laravel') }}</h1>

            {{-- Show login form if guest --}}
            @guest
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('email')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('password')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-600">Remember me</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:text-indigo-500">Forgot password?</a>
                    </div>

                    <button type="submit"
                            class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">
                        Login
                    </button>
                </form>
            @else
                {{-- Show logout if authenticated --}}
                <div class="text-center mb-4">
                    <p class="text-gray-700">Welcome back, {{ auth()->user()->name }}!</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700">
                        Logout
                    </button>
                </form>

                {{-- Optional: link to dashboard --}}
                <div class="mt-4 text-center">
                    <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:text-indigo-500">Go to Dashboard</a>
                </div>
            @endguest
        </div>
    </div>
</body>
</html>