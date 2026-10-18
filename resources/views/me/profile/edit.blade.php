@extends('layouts.app')
@section('title', 'My Profile')

@section('content')
    <x-page-header title="My Profile" subtitle="View your employment details and update your account." />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div>
            <x-card title="Account Details" subtitle="Managed by HR.">
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Employee Code</dt>
                        <dd class="font-medium text-slate-800">{{ $user->employee_code ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Department</dt>
                        <dd class="font-medium text-slate-800">{{ $user->department?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Position</dt>
                        <dd class="font-medium text-slate-800">{{ $user->jobPosition?->title ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Shift</dt>
                        <dd class="font-medium text-slate-800">{{ $user->shift?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Hire Date</dt>
                        <dd class="font-medium text-slate-800">{{ $user->hire_date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>

        <div class="lg:col-span-2">
            <x-card title="Edit Profile" subtitle="Update your contact details and password.">
                <form method="POST" action="{{ route('me.profile.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-field label="Full Name" name="name" :required="true">
                            <x-input name="name" value="{{ old('name', $user->name) }}" required />
                        </x-field>
                        <x-field label="Email" name="email" :required="true">
                            <x-input name="email" type="email" value="{{ old('email', $user->email) }}" required />
                        </x-field>

                        <x-field label="Phone" name="phone">
                            <x-input name="phone" value="{{ old('phone', $user->phone) }}" />
                        </x-field>
                        <x-field label="Address" name="address" class="sm:col-span-2">
                            <x-textarea name="address">{{ old('address', $user->address) }}</x-textarea>
                        </x-field>

                        <x-field label="Password" name="password" hint="Leave blank to keep current">
                            <x-input name="password" type="password" autocomplete="new-password" />
                        </x-field>
                        <x-field label="Confirm Password" name="password_confirmation">
                            <x-input name="password_confirmation" type="password" autocomplete="new-password" />
                        </x-field>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2">
                        <x-button type="submit">Save Changes</x-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
@endsection
