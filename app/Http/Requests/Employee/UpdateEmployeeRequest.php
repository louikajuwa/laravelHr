<?php

namespace App\Http\Requests\Employee;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $employee = $this->route('employee');
            if (! $employee || $this->input('role') === 'admin' || ! $employee->isAdmin()) {
                return;
            }

            if ($employee->id === $this->user()?->id) {
                $validator->errors()->add('role', 'You cannot remove your own admin role.');
            } elseif ($employee->is_active
                && ! User::role('admin')->where('is_active', true)->where('id', '!=', $employee->id)->exists()) {
                $validator->errors()->add('role', 'You cannot demote the last active admin.');
            }
        });
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        $id = $this->route('employee')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(['admin', 'employee'])],
            'department_id' => ['nullable', 'exists:departments,id'],
            'job_position_id' => ['nullable', 'exists:job_positions,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'gender' => ['nullable', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date'],
            'hire_date' => ['nullable', 'date'],
            'employment_type' => ['nullable', 'in:full_time,part_time,contract'],
        ];
    }
}
