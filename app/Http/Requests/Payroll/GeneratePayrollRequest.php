<?php

namespace App\Http\Requests\Payroll;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class GeneratePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'user_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->hasAny(['period_start', 'period_end'])) {
                return;
            }
            $start = Carbon::parse($this->input('period_start'))->startOfDay();
            $end = Carbon::parse($this->input('period_end'))->startOfDay();
            $monthStart = $start->copy()->startOfMonth();
            $monthEnd = $start->copy()->endOfMonth();
            $fullMonth = $start->isSameDay($monthStart) && $end->isSameDay($monthEnd);
            $firstHalf = $start->isSameDay($monthStart) && $end->isSameDay($monthStart->copy()->day(15));
            $secondHalf = $start->isSameDay($monthStart->copy()->day(16)) && $end->isSameDay($monthEnd);
            if (! $fullMonth && ! $firstHalf && ! $secondHalf) {
                $validator->errors()->add('period_end', 'A payroll period must be a full calendar month (1st to last day), or a half of one within the same month (1st-15th or 16th-last day).');
            }
        }];
    }
}
