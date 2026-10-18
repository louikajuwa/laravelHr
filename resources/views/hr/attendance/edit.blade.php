@extends('layouts.app')
@section('title', 'Edit Attendance')

@section('content')
    <x-page-header title="Edit Attendance" subtitle="Correct attendance times and status.">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('hr.attendance.index')">Back to list</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-card title="Attendance Record"
                    subtitle="{{ $attendance->user->name }} ({{ $attendance->user->employee_code }}) · {{ $attendance->date->format('M d, Y') }}">
                <form method="POST" action="{{ route('hr.attendance.update', $attendance) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field label="Time In" name="time_in">
                            <x-input name="time_in" type="datetime-local" value="{{ old('time_in', $attendance->time_in?->format('Y-m-d\TH:i')) }}" />
                        </x-field>
                        <x-field label="Time Out" name="time_out">
                            <x-input name="time_out" type="datetime-local" value="{{ old('time_out', $attendance->time_out?->format('Y-m-d\TH:i')) }}" />
                        </x-field>
                    </div>

                    <x-field label="Status" name="status">
                        <x-select name="status">
                            @php $st = old('status', $attendance->status); @endphp
                            <option value="present" @selected($st === 'present')>Present</option>
                            <option value="late" @selected($st === 'late')>Late</option>
                            <option value="absent" @selected($st === 'absent')>Absent</option>
                            <option value="on_leave" @selected($st === 'on_leave')>On Leave</option>
                            <option value="half_day" @selected($st === 'half_day')>Half Day</option>
                            <option value="holiday" @selected($st === 'holiday')>Holiday</option>
                        </x-select>
                    </x-field>

                    <x-field label="Remarks" name="remarks">
                        <x-textarea name="remarks">{{ old('remarks', $attendance->remarks) }}</x-textarea>
                    </x-field>

                    <x-field label="Reason for correction" name="reason" :required="true">
                        <x-textarea name="reason">{{ old('reason') }}</x-textarea>
                    </x-field>

                    <div class="flex items-center gap-3">
                        <x-button type="submit" variant="primary">Save Changes</x-button>
                        <x-button variant="secondary" :href="route('hr.attendance.index')">Cancel</x-button>
                    </div>
                </form>
            </x-card>
        </div>

        <div class="lg:col-span-1">
            <x-card title="Correction History">
                @forelse ($attendance->corrections as $c)
                    <div class="border-b border-slate-100 py-3 last:border-0">
                        <p class="text-sm font-medium text-slate-700">{{ $c->field }}</p>
                        <p class="mt-1 text-xs text-slate-500">
                            <span class="text-slate-500">{{ $c->old_value ?? '—' }}</span>
                            <span class="mx-1">→</span>
                            <span class="text-slate-700">{{ $c->new_value ?? '—' }}</span>
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            by {{ $c->correctedBy?->name ?? 'System' }} ·
                            <span data-epoch="{{ $c->epoch('created_at') }}" data-format="MMM D, YYYY · h:mm A">—</span>
                        </p>
                    </div>
                @empty
                    <x-empty message="No corrections recorded yet." icon="clock" />
                @endforelse
            </x-card>
        </div>
    </div>
@endsection
