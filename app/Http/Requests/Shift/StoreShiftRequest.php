<?php

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('working_days')) {
            $this->merge(['working_days' => []]);
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'grace_period_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'break_minutes' => ['required', 'integer', 'min:0', 'max:480'],
            'overtime_threshold_minutes' => ['required', 'integer', 'min:0', 'max:480'],
            'working_days' => ['array', 'min:1'],
            'working_days.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
