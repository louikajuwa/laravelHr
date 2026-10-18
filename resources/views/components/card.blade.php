@props(['title' => null, 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white shadow-sm']) }}>
    @if ($title || $subtitle || isset($actions))
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                @if ($title)<h3 class="text-sm font-semibold text-slate-800">{{ $title }}</h3>@endif
                @if ($subtitle)<p class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div class="{{ $attributes->get('flush') ? '' : 'p-5' }}">
        {{ $slot }}
    </div>
</div>
