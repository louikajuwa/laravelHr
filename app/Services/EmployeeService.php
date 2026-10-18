<?php

namespace App\Services;

use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = new User;
            $user->fill($this->fillable($data));
            $user->password = Hash::make($data['password']);
            $user->is_active = $data['is_active'] ?? true;
            $user->save();

            $user->employee_code = $data['employee_code'] ?? $this->generateCode($user);
            $user->save();

            $role = $data['role'] ?? 'employee';
            $user->syncRoles([$role]);

            $this->seedLeaveBalances($user);

            $this->audit->log('user.created', $user, [], $user->only([
                'name', 'email', 'employee_code', 'department_id', 'job_position_id',
            ]), "Created employee {$user->name}");

            return $user;
        });
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $before = $user->only(['name', 'email', 'department_id', 'job_position_id', 'shift_id', 'employment_type']);

        $user->fill($this->fillable($data));
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        if (! empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        $this->audit->log('user.updated', $user, $before, $user->only(array_keys($before)),
            "Updated employee {$user->name}");

        return $user;
    }

    public function setActive(User $user, bool $active): User
    {
        $user->is_active = $active;
        $user->save();

        $this->audit->log($active ? 'user.activated' : 'user.deactivated', $user, [], [],
            ($active ? 'Activated' : 'Deactivated')." {$user->name}");

        return $user;
    }

    private function seedLeaveBalances(User $user): void
    {
        $year = (int) now()->year;
        foreach (LeaveType::where('is_active', true)->get() as $type) {
            $user->leaveBalances()->updateOrCreate(
                ['leave_type_id' => $type->id, 'year' => $year],
                ['allocated' => $type->default_days_per_year, 'used' => 0, 'pending' => 0, 'carried_over' => 0],
            );
        }
    }

    private function generateCode(User $user): string
    {
        return 'EMP-'.now()->year.'-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function fillable(array $data): array
    {
        return collect($data)->only([
            'name', 'email', 'employee_code', 'department_id', 'job_position_id', 'shift_id',
            'phone', 'address', 'date_of_birth', 'gender', 'hire_date', 'employment_type', 'avatar_path',
        ])->toArray();
    }
}
