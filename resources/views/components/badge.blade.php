@props(['color' => 'bg-slate-100 text-slate-600'])

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold $color"]) }}>
    {{ $slot }}
</span>
