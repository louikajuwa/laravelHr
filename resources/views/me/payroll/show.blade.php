@extends('layouts.app')
@section('title', 'Payslip')

@section('content')
    <x-page-header title="Payslip"
                   subtitle="{{ $payroll->label ?? $payroll->period_start->format('M j, Y').' – '.$payroll->period_end->format('M j, Y') }}">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('me.payroll.payslip', $payroll)"><x-icon name="download" class="h-4 w-4" /> Download PDF</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Earnings">
                <div class="overflow-x-auto"><table class="min-w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($payroll->items->where('type', 'earning') as $item)
                            <tr>
                                <td class="py-2.5 text-slate-600">{{ $item->label }}</td>
                                <td class="py-2.5 text-right font-medium text-slate-800">{{ number_format($item->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td class="py-2.5 text-slate-500">No earnings recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </x-card>

            <x-card title="Deductions">
                <div class="overflow-x-auto"><table class="min-w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($payroll->items->where('type', 'deduction') as $item)
                            <tr>
                                <td class="py-2.5 text-slate-600">{{ $item->label }}</td>
                                <td class="py-2.5 text-right font-medium text-rose-600">-{{ number_format($item->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td class="py-2.5 text-slate-500">No deductions recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </x-card>
        </div>

        <div>
            <x-card title="Summary">
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Gross</dt>
                        <dd class="font-medium text-slate-800">{{ number_format($payroll->gross, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Total Deductions</dt>
                        <dd class="font-medium text-rose-600">-{{ number_format($payroll->deductions_total, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                        <dt class="text-slate-500">Net Pay</dt>
                        <dd class="text-2xl font-bold text-slate-900">{{ number_format($payroll->net_pay, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                        <dt class="text-slate-500">Status</dt>
                        <dd><x-badge :color="\App\Enums\PayrollStatus::badgeFor($payroll->status)">{{ ucfirst($payroll->status) }}</x-badge></dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
@endsection
