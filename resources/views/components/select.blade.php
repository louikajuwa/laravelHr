@props(['name' => null])

@php
    $invalid = $name && $errors->has($name);
    $extra = ['id' => $name, 'name' => $name];
    if ($invalid) { $extra['aria-invalid'] = 'true'; $extra['aria-describedby'] = $name.'-error'; }
    if ($attributes->has('required')) { $extra['aria-required'] = 'true'; }
    $border = $invalid
        ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500'
        : 'border-slate-300 focus:border-accent-500 focus:ring-accent-500';
@endphp

<select {{ $attributes->merge($extra + [
    'class' => 'block w-full rounded-xl bg-white text-sm shadow-sm '.$border,
]) }}>
    {{ $slot }}
</select>
