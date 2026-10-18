@extends('layouts.app')
@section('title', 'My Dashboard')

@section('content')
    <x-page-header :title="'Hi, '.explode(' ', $user->name)[0].' 👋'" subtitle="Here's your day at a glance." />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Clock card --}}
        <x-card class="lg:col-span-1">
            <div id="clock-card"
                 data-clockin-url="{{ route('me.attendance.clock-in') }}"
                 data-clockout-url="{{ route('me.attendance.clock-out') }}"
                 class="text-center">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Local time · <span data-tz-label>local</span>
                </p>
                <p id="live-clock" class="mt-1 text-3xl font-bold text-slate-900">—</p>

                <div class="mt-4 rounded-xl bg-slate-50 p-3 text-sm">
                    @if ($today && $today->time_in)
                        <p class="text-slate-500">Clocked in
                            <span class="font-semibold text-slate-800" data-epoch="{{ $today->epoch('time_in') }}" data-format="h:mm A">—</span>
                            @if ($today->is_late)<x-badge color="bg-amber-100 text-amber-700">Late {{ $today->late_minutes }}m</x-badge>@endif
                        </p>
                        @if ($today->time_out)
                            <p class="mt-1 text-slate-500">Clocked out
                                <span class="font-semibold text-slate-800" data-epoch="{{ $today->epoch('time_out') }}" data-format="h:mm A">—</span>
                            </p>
                        @endif
                    @else
                        <p class="text-slate-500">You haven't clocked in yet today.</p>
                    @endif
                </div>

                <div class="mt-4 flex gap-2">
                    <button id="clock-in-btn" @if($today && $today->time_in) disabled @endif
                            class="flex-1 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50">
                        Clock In
                    </button>
                    <button id="clock-out-btn" @if(!$today || !$today->time_in || $today->time_out) disabled @endif
                            class="flex-1 rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-900 disabled:opacity-50">
                        Clock Out
                    </button>
                </div>
            </div>
        </x-card>

        {{-- Month stats --}}
        <div class="grid grid-cols-2 gap-4 lg:col-span-2">
            <x-stat label="Days Present" :value="$monthStats['present']" sub="This month" icon="clock" tone="emerald" />
            <x-stat label="Late Count" :value="$monthStats['late']" :sub="$monthStats['late_minutes'].' minutes total'" icon="alert" tone="amber" />
            <x-stat label="Overtime" :value="round($monthStats['overtime_minutes'] / 60, 1).' h'" sub="This month" icon="chart" tone="violet" />
            <x-stat label="Latest Net Pay"
                    :value="$latestPayroll ? number_format($latestPayroll->net_pay, 2) : '—'"
                    :sub="$latestPayroll ? $latestPayroll->label : 'No payslip yet'" icon="cash" tone="accent" />
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Leave balances --}}
        <x-card title="Leave Balances" :subtitle="now()->year">
            <x-slot:actions>
                <x-button variant="secondary" :href="route('me.leave.create')" class="!py-1.5 !text-xs">Request Leave</x-button>
            </x-slot:actions>
            @forelse ($balances as $b)
                <div class="flex items-center justify-between border-b border-slate-100 py-2.5 last:border-0">
                    <span class="text-sm text-slate-600">{{ $b->leaveType->name }}</span>
                    <span class="text-sm"><span class="font-bold text-slate-900">{{ rtrim(rtrim(number_format($b->available, 1), '0'), '.') }}</span>
                        <span class="text-slate-500">/ {{ rtrim(rtrim(number_format($b->allocated, 1), '0'), '.') }} days</span></span>
                </div>
            @empty
                <x-empty message="No leave balances allocated yet." icon="tag" />
            @endforelse
        </x-card>

        {{-- Recent leave --}}
        <x-card title="Recent Leave Requests">
            <x-slot:actions>
                <a href="{{ route('me.leave.index') }}" class="text-xs font-semibold text-accent-600 hover:underline">View all</a>
            </x-slot:actions>
            @forelse ($recentLeave as $lr)
                <div class="flex items-center justify-between border-b border-slate-100 py-2.5 last:border-0">
                    <div>
                        <p class="text-sm font-medium text-slate-700">{{ $lr->leaveType->name }}</p>
                        <p class="text-xs text-slate-500">
                            <span data-epoch="{{ $lr->start_date->timestamp }}" data-format="MMM D">{{ $lr->start_date->format('M d') }}</span>
                            – <span data-epoch="{{ $lr->end_date->timestamp }}" data-format="MMM D, YYYY">{{ $lr->end_date->format('M d, Y') }}</span>
                        </p>
                    </div>
                    <x-badge :color="\App\Enums\LeaveStatus::badgeFor($lr->status)">{{ ucfirst($lr->status) }}</x-badge>
                </div>
            @empty
                <x-empty message="No leave requests yet." icon="calendar" />
            @endforelse
        </x-card>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/attendance.js') }}"></script>
@endpush
