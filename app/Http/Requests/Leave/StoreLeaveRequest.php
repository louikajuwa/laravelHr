<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveRequest extends FormRequest
{
    public const MAX_RANGE_DAYS = 365;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        $start = $this->input('start_date');
        $max = is_string($start) && strtotime($start) !== false
            ? date('Y-m-d', strtotime($start.' +'.self::MAX_RANGE_DAYS.' days'))
            : null;

        return [
            'leave_type_id' => ['required', Rule::exists('leave_types', 'id')->where('is_active', true)],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => array_filter(['required', 'date', 'after_or_equal:start_date', $max ? 'before_or_equal:'.$max : null]),
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'leave_type_id.exists' => 'The selected leave type is not available.',
            'end_date.before_or_equal' => 'A leave request cannot span more than '.self::MAX_RANGE_DAYS.' days.',
        ];
    }
}
