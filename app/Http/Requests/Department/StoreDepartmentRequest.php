<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
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
        $id = $this->route('department')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($id)],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
