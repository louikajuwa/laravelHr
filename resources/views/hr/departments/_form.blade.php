@php $department = $department ?? null; @endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <x-field label="Name" name="name" :required="true">
        <x-input name="name" value="{{ old('name', $department?->name) }}" required />
    </x-field>
    <x-field label="Code" name="code">
        <x-input name="code" value="{{ old('code', $department?->code) }}" />
    </x-field>

    <x-field label="Description" name="description" class="md:col-span-2">
        <x-textarea name="description">{{ old('description', $department?->description) }}</x-textarea>
    </x-field>

    <x-field label="Manager" name="manager_id">
        <x-select name="manager_id">
            <option value="">— None —</option>
            @foreach ($managers as $m)
                <option value="{{ $m->id }}" @selected(old('manager_id', $department?->manager_id) == $m->id)>{{ $m->name }}</option>
            @endforeach
        </x-select>
    </x-field>
    <x-field label="Shift" name="shift_id">
        <x-select name="shift_id">
            <option value="">— None —</option>
            @foreach ($shifts as $s)
                <option value="{{ $s->id }}" @selected(old('shift_id', $department?->shift_id) == $s->id)>{{ $s->name }}</option>
            @endforeach
        </x-select>
    </x-field>

    <div class="md:col-span-2">
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $department->is_active ?? true)) class="rounded border-slate-300 text-accent-600 focus:ring-accent-500"> Active
        </label>
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-2">
    <x-button variant="secondary" :href="route('hr.departments.index')">Cancel</x-button>
    <x-button type="submit">{{ $department ? 'Save Changes' : 'Create Department' }}</x-button>
</div>
