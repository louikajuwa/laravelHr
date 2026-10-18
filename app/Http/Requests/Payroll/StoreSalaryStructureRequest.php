<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rows = collect($this->input('allowances', []))
            ->filter(fn ($a) => isset($a['name']) && $a['name'] !== '')
            ->values()
            ->all();
        $this->merge(['allowances' => $rows ?: null]);
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'pay_frequency' => ['required', 'in:monthly,biweekly'],
            'working_days_per_month' => ['required', 'integer', 'min:1', 'max:31'],
            'overtime_rate_multiplier' => ['required', 'numeric', 'min:0'],
            'late_deduction_per_minute' => ['required', 'numeric', 'min:0'],
            'absence_deduction_mode' => ['required', 'in:daily_rate,fixed'],
            'perfect_attendance_bonus' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'allowances' => ['nullable', 'array'],
            'allowances.*.name' => ['nullable', 'string', 'max:100'],
            'allowances.*.amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
