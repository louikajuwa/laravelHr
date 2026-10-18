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
            if (! $start->isSameDay($start->copy()->startOfMonth()) || ! $end->isSameDay($start->copy()->endOfMonth())) {
                $validator->errors()->add('period_end', 'A payroll period must be exactly one full calendar month (from the 1st to the last day of the same month).');
            }
        }];
    }
}
