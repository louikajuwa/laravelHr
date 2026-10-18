@props(['label' => null, 'name' => null, 'required' => false, 'hint' => null])

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif class="mb-1 block text-sm font-medium text-slate-700">
            {{ $label }} @if ($required)<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if ($hint)<p @if($name) id="{{ $name }}-hint" @endif class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
    @if ($name && $errors->has($name))
        <p id="{{ $name }}-error" class="mt-1 text-xs text-rose-600">{{ $errors->first($name) }}</p>
    @endif
</div>
