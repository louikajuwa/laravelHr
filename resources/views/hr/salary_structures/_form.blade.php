@php
    $s = $structure ?? null;
    $allowances = $s?->allowances ?? [];
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <x-field label="Employee" name="user_id" :required="true" class="md:col-span-2">
        @if ($s)
            <x-select name="user_id" disabled>
                @foreach ($employees as $e)
                    <option value="{{ $e->id }}" @selected($e->id == $s->user_id)>{{ $e->employee_code }} — {{ $e->name }}</option>
                @endforeach
            </x-select>
            <input type="hidden" name="user_id" value="{{ $s->user_id }}">
        @else
            <x-select name="user_id" required>
                <option value="">— Select employee —</option>
                @foreach ($employees as $e)
                    <option value="{{ $e->id }}" @selected(old('user_id', $selectedUser ?? null) == $e->id)>{{ $e->employee_code }} — {{ $e->name }}</option>
                @endforeach
            </x-select>
        @endif
    </x-field>

    <x-field label="Basic Salary" name="basic_salary" :required="true">
        <x-input name="basic_salary" type="number" step="0.01" value="{{ old('basic_salary', $s?->basic_salary) }}" required />
    </x-field>
    <x-field label="Pay Frequency" name="pay_frequency" :required="true">
        <x-select name="pay_frequency">
            @foreach (['monthly' => 'Monthly', 'biweekly' => 'Biweekly'] as $k => $v)
                <option value="{{ $k }}" @selected(old('pay_frequency', $s?->pay_frequency) == $k)>{{ $v }}</option>
            @endforeach
        </x-select>
    </x-field>

    <x-field label="Working Days / Month" name="working_days_per_month" :required="true">
        <x-input name="working_days_per_month" type="number" value="{{ old('working_days_per_month', $s?->working_days_per_month ?? 22) }}" required />
    </x-field>
    <x-field label="Overtime Rate Multiplier" name="overtime_rate_multiplier" :required="true">
        <x-input name="overtime_rate_multiplier" type="number" step="0.05" value="{{ old('overtime_rate_multiplier', $s?->overtime_rate_multiplier ?? 1.25) }}" required />
    </x-field>

    <x-field label="Late Deduction / Minute" name="late_deduction_per_minute" :required="true">
        <x-input name="late_deduction_per_minute" type="number" step="0.01" value="{{ old('late_deduction_per_minute', $s?->late_deduction_per_minute ?? 0) }}" required />
    </x-field>
    <x-field label="Absence Deduction Mode" name="absence_deduction_mode">
        <x-select name="absence_deduction_mode">
            @foreach (['daily_rate' => 'Daily rate', 'fixed' => 'Fixed'] as $k => $v)
                <option value="{{ $k }}" @selected(old('absence_deduction_mode', $s?->absence_deduction_mode) == $k)>{{ $v }}</option>
            @endforeach
        </x-select>
    </x-field>

    <x-field label="Perfect Attendance Bonus" name="perfect_attendance_bonus" :required="true">
        <x-input name="perfect_attendance_bonus" type="number" step="0.01" value="{{ old('perfect_attendance_bonus', $s?->perfect_attendance_bonus ?? 0) }}" required />
    </x-field>
    <x-field label="Effective From" name="effective_from" :required="true">
        <x-input name="effective_from" type="date" value="{{ old('effective_from', $s?->effective_from?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required />
    </x-field>
</div>

<div class="mt-6">
    <h4 class="mb-3 text-sm font-semibold text-slate-800">Allowances</h4>
    <div class="space-y-3">
        @for ($i = 0; $i < 3; $i++)
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <x-input name="allowances[{{ $i }}][name]" placeholder="Allowance name" value="{{ old("allowances.$i.name", $allowances[$i]['name'] ?? '') }}" />
                <x-input name="allowances[{{ $i }}][amount]" type="number" step="0.01" placeholder="Amount" value="{{ old("allowances.$i.amount", $allowances[$i]['amount'] ?? '') }}" />
            </div>
        @endfor
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-2">
    <x-button variant="secondary" :href="route('hr.salary-structures.index')">Cancel</x-button>
    <x-button type="submit">{{ $s ? 'Save Changes' : 'Create Structure' }}</x-button>
</div>
