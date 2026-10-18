@php $leaveType = $leaveType ?? null; @endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <x-field label="Name" name="name" :required="true">
        <x-input name="name" value="{{ old('name', $leaveType?->name) }}" required />
    </x-field>
    <x-field label="Code" name="code" :required="true">
        <x-input name="code" value="{{ old('code', $leaveType?->code) }}" required />
    </x-field>

    <x-field label="Default Days / Year" name="default_days_per_year">
        <x-input name="default_days_per_year" type="number" step="0.5" value="{{ old('default_days_per_year', $leaveType?->default_days_per_year) }}" />
    </x-field>
    <x-field label="Max Carry-over Days" name="max_carry_over_days">
        <x-input name="max_carry_over_days" type="number" value="{{ old('max_carry_over_days', $leaveType?->max_carry_over_days) }}" />
        <p class="mt-1 text-xs text-slate-500">0 = no limit. Unused days from last year carry over up to this amount.</p>
    </x-field>

    <div class="md:col-span-2 space-y-3">
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_paid" value="1" @checked(old('is_paid', $leaveType->is_paid ?? true)) class="rounded border-slate-300 text-accent-600 focus:ring-accent-500"> Paid leave
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="requires_approval" value="1" @checked(old('requires_approval', $leaveType->requires_approval ?? true)) class="rounded border-slate-300 text-accent-600 focus:ring-accent-500"> Requires approval
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="carry_over" value="1" @checked(old('carry_over', $leaveType->carry_over ?? false)) class="rounded border-slate-300 text-accent-600 focus:ring-accent-500"> Allow carry-over
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $leaveType->is_active ?? true)) class="rounded border-slate-300 text-accent-600 focus:ring-accent-500"> Active
        </label>
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-2">
    <x-button variant="secondary" :href="route('hr.leave-types.index')">Cancel</x-button>
    <x-button type="submit">{{ $leaveType ? 'Save Changes' : 'Create Leave Type' }}</x-button>
</div>
