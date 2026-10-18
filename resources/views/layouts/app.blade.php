<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        sidebar: { DEFAULT: '#0f172a', 900: '#0b1222' },
                        accent: {
                            50: '#eef2ff', 100: '#e0e7ff', 400: '#818cf8', 500: '#6366f1',
                            600: '#4f46e5', 700: '#4338ca',
                        },
                    },
                },
            },
        };
    </script>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1.11.13/dayjs.min.js" integrity="sha384-DpVxUeeBWjUvUV1czyIHJAjh+jYUZFu2lLakbdua5vbwOrBGi1UgaKCHjTC+x3Ky" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" integrity="sha384-vsrfeLOOY6KuIYKDlmVH5UiBmgIdB1oEf7p01YgWHuqmOHfZr374+odEv96n9tNC" crossorigin="anonymous"></script>
    <style>[data-sidebar][inert]{visibility:hidden;transition:transform .15s,visibility 0s .15s}</style>
</head>
<body class="h-full bg-slate-100 font-sans text-slate-800 antialiased">
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-xl focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-accent-700 focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-accent-500">Skip to main content</a>
@php $admin = auth()->user()->isAdmin(); @endphp

<div class="min-h-full">
    {{-- Sidebar --}}
    <aside id="app-sidebar" data-sidebar
           class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full transform overflow-y-auto bg-sidebar transition lg:translate-x-0">
        <div class="flex h-16 items-center gap-2.5 px-5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-accent-600 text-white font-bold">HR</span>
            <div class="leading-tight">
                <p class="text-sm font-bold text-white">{{ config('app.name') }}</p>
                <p class="text-[11px] text-slate-400">{{ $admin ? 'HR / Admin' : 'Employee' }}</p>
            </div>
        </div>

        <nav aria-label="Main navigation" class="space-y-1 px-3 pb-8 pt-2">
            @if ($admin)
                <x-nav-link icon="grid" label="Dashboard" :href="route('hr.dashboard')" :active="request()->routeIs('hr.dashboard')" />
                <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">People</p>
                <x-nav-link icon="users" label="Employees" :href="route('hr.employees.index')" :active="request()->routeIs('hr.employees.*')" />
                <x-nav-link icon="building" label="Departments" :href="route('hr.departments.index')" :active="request()->routeIs('hr.departments.*')" />
                <x-nav-link icon="briefcase" label="Positions" :href="route('hr.positions.index')" :active="request()->routeIs('hr.positions.*')" />
                <x-nav-link icon="clock" label="Shifts" :href="route('hr.shifts.index')" :active="request()->routeIs('hr.shifts.*')" />
                <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Time & Leave</p>
                <x-nav-link icon="clock" label="Attendance" :href="route('hr.attendance.index')" :active="request()->routeIs('hr.attendance.index') || request()->routeIs('hr.attendance.edit')" />
                <x-nav-link icon="chart" label="Reports" :href="route('hr.attendance.reports')" :active="request()->routeIs('hr.attendance.reports')" />
                <x-nav-link icon="calendar" label="Leave Requests" :href="route('hr.leave.index')" :active="request()->routeIs('hr.leave.index')" />
                <x-nav-link icon="tag" label="Leave Types" :href="route('hr.leave-types.index')" :active="request()->routeIs('hr.leave-types.*')" />
                <x-nav-link icon="alert" label="Late Arrivals" :href="route('hr.late-arrivals.index')" :active="request()->routeIs('hr.late-arrivals.*')" />
                <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Payroll</p>
                <x-nav-link icon="cash" label="Payroll" :href="route('hr.payroll.index')" :active="request()->routeIs('hr.payroll.*')" />
                <x-nav-link icon="adjust" label="Salary Structures" :href="route('hr.salary-structures.index')" :active="request()->routeIs('hr.salary-structures.*')" />
                <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">System</p>
                <x-nav-link icon="shield" label="Audit Log" :href="route('hr.audit.index')" :active="request()->routeIs('hr.audit.*')" />
            @else
                <x-nav-link icon="grid" label="Dashboard" :href="route('me.dashboard')" :active="request()->routeIs('me.dashboard')" />
                <x-nav-link icon="clock" label="My Attendance" :href="route('me.attendance.index')" :active="request()->routeIs('me.attendance.*')" />
                <x-nav-link icon="calendar" label="Leave Requests" :href="route('me.leave.index')" :active="request()->routeIs('me.leave.index') || request()->routeIs('me.leave.create')" />
                <x-nav-link icon="tag" label="Leave Balances" :href="route('me.leave.balances')" :active="request()->routeIs('me.leave.balances')" />
                <x-nav-link icon="cash" label="My Payroll" :href="route('me.payroll.index')" :active="request()->routeIs('me.payroll.*')" />
                <x-nav-link icon="user" label="My Profile" :href="route('me.profile.edit')" :active="request()->routeIs('me.profile.*')" />
            @endif
        </nav>
    </aside>

    <div data-sidebar-backdrop class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden"></div>

    {{-- Main column --}}
    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/80 px-4 backdrop-blur lg:px-8">
            <button type="button" data-sidebar-toggle aria-label="Open navigation menu" aria-controls="app-sidebar" aria-expanded="false"
                    class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 lg:hidden">
                <x-icon name="menu" />
            </button>
            <div class="flex-1"></div>
            <div class="hidden items-center gap-1.5 text-xs text-slate-500 sm:flex">
                <x-icon name="clock" class="h-4 w-4" />
                <span data-tz-label>local</span> ·
                <span id="topbar-clock"></span>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right leading-tight">
                    <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-slate-500">{{ auth()->user()->employee_code }}</p>
                </div>
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-accent-100 text-sm font-bold text-accent-700">
                    {{ auth()->user()->initials() }}
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" aria-label="Log out" title="Log out"
                            class="rounded-lg p-2 text-slate-500 hover:bg-rose-50 hover:text-rose-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500">
                        <x-icon name="logout" />
                    </button>
                </form>
            </div>
        </header>

        <main id="main-content" tabindex="-1" class="mx-auto max-w-7xl px-4 py-8 focus:outline-none lg:px-8">
            <x-flash />
            @yield('content')
        </main>
    </div>
</div>

<script>
    (function () {
        function tick() {
            var el = document.getElementById('topbar-clock');
            if (el && window.dayjs) el.textContent = dayjs().format('h:mm A');
        }
        tick(); setInterval(tick, 30000);
    })();
</script>
{{-- Confirmation modal (shared, populated by HR.confirm() in app.js) --}}
<div id="hr-confirm-modal" data-modal role="dialog" aria-modal="true" aria-labelledby="hr-confirm-title" aria-describedby="hr-confirm-message"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/50 p-4">
    <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-rose-100">
                <svg aria-hidden="true" class="h-5 w-5 text-rose-600" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </span>
            <div class="flex-1">
                <h3 id="hr-confirm-title" class="text-base font-semibold text-slate-800">Confirm action</h3>
                <p id="hr-confirm-message" class="mt-1 text-sm text-slate-600">Are you sure you want to proceed?</p>
            </div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button data-modal-close type="button"
                    class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500">
                Cancel
            </button>
            <button id="hr-confirm-ok" type="button"
                    class="rounded-xl px-4 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 transition">
                Confirm
            </button>
        </div>
    </div>
</div>

<script src="{{ asset('js/time.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/api-list.js') }}"></script>
@stack('scripts')
</body>
</html>
