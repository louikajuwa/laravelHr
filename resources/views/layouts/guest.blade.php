<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
</head>
<body class="h-full bg-slate-950 font-sans text-slate-100 antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-xl focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-accent-700 focus:outline-none focus:ring-2 focus:ring-accent-500">Skip to main content</a>
    <div class="flex min-h-full">
        {{-- Brand panel --}}
        <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-gradient-to-br from-accent-700 via-indigo-800 to-slate-900 p-12 lg:flex">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15 text-lg font-bold">HR</span>
                <span class="text-lg font-bold">{{ config('app.name') }}</span>
            </div>
            <div>
                <p class="max-w-md text-3xl font-bold leading-tight">Attendance, leave & payroll — one lightweight HRIS.</p>
                <p class="mt-4 max-w-md text-sm text-indigo-100/80">Clock in, manage leave, and run payroll from a single dashboard. Times are shown in your local timezone.</p>
            </div>
            <p class="text-xs text-indigo-200/80">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
        </div>

        {{-- Form panel --}}
        <main id="main-content" tabindex="-1" class="flex w-full items-center justify-center px-6 py-12 focus:outline-none lg:w-1/2">
            <div class="w-full max-w-sm">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
