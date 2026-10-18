@php
    $shift = $shift ?? null;
    $days = ['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'];
    $selectedDays = old('working_days', $shift?->working_days ?? ['mon', 'tue', 'wed', 'thu', 'fri']);
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <x-field label="Name" name="name" :required="true" class="md:col-span-2">
        <x-input name="name" value="{{ old('name', $shift?->name) }}" required />
    </x-field>

    <x-field label="Start Time" name="start_time" :required="true">
        <x-input name="start_time" type="time" value="{{ old('start_time', isset($shift) ? \Illuminate\Support\Str::substr($shift->start_time, 0, 5) : '09:00') }}" required />
    </x-field>
    <x-field label="End Time" name="end_time" :required="true">
        <x-input name="end_time" type="time" value="{{ old('end_time', isset($shift) ? \Illuminate\Support\Str::substr($shift->end_time, 0, 5) : '17:00') }}" required />
    </x-field>

    <x-field label="Grace Period (minutes)" name="grace_period_minutes">
        <x-input name="grace_period_minutes" type="number" value="{{ old('grace_period_minutes', $shift?->grace_period_minutes ?? 0) }}" />
    </x-field>
    <x-field label="Break (minutes)" name="break_minutes">
        <x-input name="break_minutes" type="number" value="{{ old('break_minutes', $shift?->break_minutes ?? 0) }}" />
    </x-field>

    <x-field label="Overtime Threshold (minutes)" name="overtime_threshold_minutes">
        <x-input name="overtime_threshold_minutes" type="number" value="{{ old('overtime_threshold_minutes', $shift?->overtime_threshold_minutes ?? 0) }}" />
    </x-field>

    <x-field label="Working Days" name="working_days" class="md:col-span-2">
        <div class="flex flex-wrap gap-3">
            @foreach ($days as $value => $label)
                <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700">
                    <input type="checkbox" name="working_days[]" value="{{ $value }}" @checked(in_array($value, $selectedDays)) class="rounded border-slate-300 text-accent-600 focus:ring-accent-500"> {{ $label }}
                </label>
            @endforeach
        </div>
    </x-field>

    <div class="md:col-span-2">
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $shift->is_active ?? true)) class="rounded border-slate-300 text-accent-600 focus:ring-accent-500"> Active
        </label>
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-2">
    <x-button variant="secondary" :href="route('hr.shifts.index')">Cancel</x-button>
    <x-button type="submit">{{ $shift ? 'Save Changes' : 'Create Shift' }}</x-button>
</div>
