@if (session('status'))
    <div data-flash class="mb-4 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0" />
        <span class="flex-1">{{ session('status') }}</span>
        <button type="button" data-flash-close class="text-emerald-500 hover:text-emerald-700">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div data-flash class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        <div class="flex items-start gap-3">
            <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />
            <ul class="flex-1 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" data-flash-close class="text-rose-500 hover:text-rose-700">&times;</button>
        </div>
    </div>
@endif
