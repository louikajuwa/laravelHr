@php $position = $position ?? null; @endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <x-field label="Title" name="title" :required="true">
        <x-input name="title" value="{{ old('title', $position?->title) }}" required />
    </x-field>
    <x-field label="Department" name="department_id">
        <x-select name="department_id">
            <option value="">— None —</option>
            @foreach ($departments as $d)
                <option value="{{ $d->id }}" @selected(old('department_id', $position?->department_id) == $d->id)>{{ $d->name }}</option>
            @endforeach
        </x-select>
    </x-field>

    <x-field label="Description" name="description" class="md:col-span-2">
        <x-textarea name="description">{{ old('description', $position?->description) }}</x-textarea>
    </x-field>
</div>

<div class="mt-6 flex items-center justify-end gap-2">
    <x-button variant="secondary" :href="route('hr.positions.index')">Cancel</x-button>
    <x-button type="submit">{{ $position ? 'Save Changes' : 'Create Position' }}</x-button>
</div>
