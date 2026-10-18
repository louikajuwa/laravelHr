<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                colors: { accent: { 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca' } },
            } },
        };
    </script>
</head>
<body class="h-full bg-slate-950 font-sans text-slate-100 antialiased">
    <div class="flex min-h-full">
        {{-- Brand panel --}}
        <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-gradient-to-br from-accent-700 via-indigo-800 to-slate-900 p-12 lg:flex">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15 text-lg font-bold">HR</span>
                <span class="text-lg font-bold">{{ config('app.name') }}</span>
            </div>
            <div>
                <h2 class="max-w-md text-3xl font-bold leading-tight">Attendance, leave & payroll — one lightweight HRIS.</h2>
                <p class="mt-4 max-w-md text-sm text-indigo-100/80">Clock in, manage leave, and run payroll from a single dashboard. Times are shown in your local timezone.</p>
            </div>
            <p class="text-xs text-indigo-200/60">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
        </div>

        {{-- Form panel --}}
        <div class="flex w-full items-center justify-center px-6 py-12 lg:w-1/2">
            <div class="w-full max-w-sm">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
