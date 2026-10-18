@props(['name' => null])

<textarea {{ $attributes->merge([
    'class' => 'block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500',
    'id' => $name,
    'name' => $name,
    'rows' => 3,
]) }}>{{ $slot }}</textarea>
