<?php

namespace App\Http\Requests\Attendance;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        /** @var Attendance|null $attendance */
        $attendance = $this->route('attendance');
        $date = $attendance?->date?->copy()->startOfDay();
        $overnight = (bool) $attendance?->shift?->crossesMidnight();

        return [
            'time_in' => ['nullable', 'date', function ($attr, $value, $fail) use ($date) {
                if ($date && ! Carbon::parse($value)->isSameDay($date)) {
                    $fail('The time in must be on the attendance date ('.$date->toDateString().').');
                }
            }],
            'time_out' => array_merge(
                ['nullable', 'date'],
                $this->filled('time_in') ? ['after:time_in'] : [],
                [function ($attr, $value, $fail) use ($date, $overnight, $attendance) {
                    if (! $this->filled('time_in') && ! $attendance?->time_in) {
                        $fail('A time out requires a time in.');

                        return;
                    }
                    if (! $date) {
                        return;
                    }
                    $out = Carbon::parse($value);
                    $ok = $out->isSameDay($date) || ($overnight && $out->isSameDay($date->copy()->addDay()));
                    if (! $ok) {
                        $fail('The time out must be on the attendance date'.($overnight ? ' or the following day.' : '.'));
                    }
                }],
            ),
            'status' => ['required', Rule::in(['present', 'late', 'absent', 'on_leave', 'half_day', 'holiday'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
