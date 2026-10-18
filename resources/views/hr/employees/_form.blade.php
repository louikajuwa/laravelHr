@php
    $e = $employee ?? null;
    $roleNames = $e ? $e->roles->pluck('name')->all() : [];
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <x-field label="Full Name" name="name" :required="true">
        <x-input name="name" value="{{ old('name', $e?->name) }}" required />
    </x-field>
    <x-field label="Email" name="email" :required="true">
        <x-input name="email" type="email" value="{{ old('email', $e?->email) }}" required />
    </x-field>

    <x-field label="Password" name="password" :required="!$e" :hint="$e ? 'Leave blank to keep current password.' : null">
        <x-input name="password" type="password" autocomplete="new-password" />
    </x-field>
    <x-field label="Role" name="role" :required="true">
        <x-select name="role">
            <option value="employee" @selected(in_array('employee', old('role') ? [old('role')] : $roleNames))>Employee</option>
            <option value="admin" @selected(in_array('admin', old('role') ? [old('role')] : $roleNames))>HR / Admin</option>
        </x-select>
    </x-field>

    <x-field label="Department" name="department_id">
        <x-select name="department_id">
            <option value="">— Unassigned —</option>
            @foreach ($departments as $d)
                <option value="{{ $d->id }}" @selected(old('department_id', $e?->department_id) == $d->id)>{{ $d->name }}</option>
            @endforeach
        </x-select>
    </x-field>
    <x-field label="Position" name="job_position_id">
        <x-select name="job_position_id">
            <option value="">— Unassigned —</option>
            @foreach ($positions as $p)
                <option value="{{ $p->id }}" @selected(old('job_position_id', $e?->job_position_id) == $p->id)>{{ $p->title }}</option>
            @endforeach
        </x-select>
    </x-field>

    <x-field label="Shift" name="shift_id" hint="Overrides department shift.">
        <x-select name="shift_id">
            <option value="">— Use department shift —</option>
            @foreach ($shifts as $s)
                <option value="{{ $s->id }}" @selected(old('shift_id', $e?->shift_id) == $s->id)>{{ $s->name }}</option>
            @endforeach
        </x-select>
    </x-field>
    <x-field label="Employment Type" name="employment_type">
        <x-select name="employment_type">
            @foreach (['full_time' => 'Full time', 'part_time' => 'Part time', 'contract' => 'Contract'] as $k => $v)
                <option value="{{ $k }}" @selected(old('employment_type', $e?->employment_type) == $k)>{{ $v }}</option>
            @endforeach
        </x-select>
    </x-field>

    <x-field label="Phone" name="phone">
        <x-input name="phone" value="{{ old('phone', $e?->phone) }}" />
    </x-field>
    <x-field label="Hire Date" name="hire_date">
        <x-input name="hire_date" type="date" value="{{ old('hire_date', $e?->hire_date?->format('Y-m-d')) }}" />
    </x-field>

    <x-field label="Date of Birth" name="date_of_birth">
        <x-input name="date_of_birth" type="date" value="{{ old('date_of_birth', $e?->date_of_birth?->format('Y-m-d')) }}" />
    </x-field>
    <x-field label="Gender" name="gender">
        <x-select name="gender">
            <option value="">—</option>
            @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $k => $v)
                <option value="{{ $k }}" @selected(old('gender', $e?->gender) == $k)>{{ $v }}</option>
            @endforeach
        </x-select>
    </x-field>

    <x-field label="Address" name="address" class="md:col-span-2">
        <x-textarea name="address">{{ old('address', $e?->address) }}</x-textarea>
    </x-field>
</div>

<div class="mt-6 flex items-center justify-end gap-2">
    <x-button variant="secondary" :href="route('hr.employees.index')">Cancel</x-button>
    <x-button type="submit">{{ $e ? 'Save Changes' : 'Create Employee' }}</x-button>
</div>
