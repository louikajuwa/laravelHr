@extends('layouts.guest')
@section('title', 'Sign in')

@section('content')
    <div class="mb-8 lg:hidden">
        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-accent-600 text-lg font-bold text-white">HR</span>
    </div>

    <h1 class="text-2xl font-bold text-white">Welcome back</h1>
    <p class="mt-1 text-sm text-slate-400">Sign in to your HR account.</p>

    @if ($errors->any())
        <div class="mt-5 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-300">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-300">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="block w-full rounded-xl border-0 bg-slate-800 px-3.5 py-2.5 text-sm text-white shadow-sm ring-1 ring-inset ring-slate-700 placeholder:text-slate-500 focus:ring-2 focus:ring-accent-500"
                   placeholder="you@company.com">
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-slate-300">Password</label>
            <input id="password" name="password" type="password" required
                   class="block w-full rounded-xl border-0 bg-slate-800 px-3.5 py-2.5 text-sm text-white shadow-sm ring-1 ring-inset ring-slate-700 placeholder:text-slate-500 focus:ring-2 focus:ring-accent-500"
                   placeholder="••••••••">
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-400">
            <input type="checkbox" name="remember" class="rounded border-slate-600 bg-slate-800 text-accent-600 focus:ring-accent-500">
            Remember me
        </label>
        <button type="submit"
                class="w-full rounded-xl bg-accent-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-700">
            Sign in
        </button>
    </form>

    <div class="mt-6 rounded-xl bg-slate-900 px-4 py-3 text-xs text-slate-400 ring-1 ring-slate-800">
        <p class="font-semibold text-slate-300">Demo accounts</p>
        <p class="mt-1">HR/Admin — <span class="text-slate-200">admin@hr.test</span> / <span class="text-slate-200">password</span></p>
        <p>Employee — <span class="text-slate-200">employee@hr.test</span> / <span class="text-slate-200">password</span></p>
    </div>
@endsection
