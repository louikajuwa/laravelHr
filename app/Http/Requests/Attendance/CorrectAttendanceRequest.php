<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class CorrectAttendanceRequest extends FormRequest
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
            'time_in' => ['nullable', 'date'],
            'time_out' => ['nullable', 'date', 'after:time_in'],
            'status' => ['required', 'in:present,late,absent,on_leave,half_day,holiday'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
