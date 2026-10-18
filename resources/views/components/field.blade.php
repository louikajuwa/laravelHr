@props(['label' => null, 'name' => null, 'required' => false, 'hint' => null])

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif class="mb-1 block text-sm font-medium text-slate-700">
            {{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if ($hint)<p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>@endif
    @if ($name && $errors->has($name))
        <p class="mt-1 text-xs text-rose-600">{{ $errors->first($name) }}</p>
    @endif
</div>
