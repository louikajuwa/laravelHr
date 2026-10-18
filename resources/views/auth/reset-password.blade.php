@extends('layouts.guest')
@section('title', 'Reset password')

@section('content')
    <div class="mb-8 lg:hidden">
        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-accent-600 text-lg font-bold text-white">HR</span>
    </div>

    <h1 class="text-2xl font-bold text-white">Reset your password</h1>
    <p class="mt-1 text-sm text-slate-300">Choose a new password for your account.</p>

    @if ($errors->any())
        <div role="alert" class="mt-5 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-300">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-300">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email"
                   @if ($errors->any()) aria-invalid="true" @endif
                   class="block w-full rounded-xl border-0 bg-slate-800 px-3.5 py-2.5 text-sm text-white shadow-sm ring-1 ring-inset ring-slate-700 placeholder:text-slate-400 focus:ring-2 focus:ring-accent-500"
                   placeholder="you@company.com">
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-slate-300">New password</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="new-password"
                   class="block w-full rounded-xl border-0 bg-slate-800 px-3.5 py-2.5 text-sm text-white shadow-sm ring-1 ring-inset ring-slate-700 placeholder:text-slate-400 focus:ring-2 focus:ring-accent-500"
                   placeholder="••••••••">
        </div>
        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-300">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                   class="block w-full rounded-xl border-0 bg-slate-800 px-3.5 py-2.5 text-sm text-white shadow-sm ring-1 ring-inset ring-slate-700 placeholder:text-slate-400 focus:ring-2 focus:ring-accent-500"
                   placeholder="••••••••">
        </div>
        <button type="submit"
                class="w-full rounded-xl bg-accent-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-700">
            Reset password
        </button>
    </form>
@endsection
