<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Shift;

class ShiftService
{
    public function __construct(private AuditLogger $audit) {}

    public function assignToUser(Shift $shift, int $userId): void
    {
        \App\Models\User::whereKey($userId)->update(['shift_id' => $shift->id]);
        $this->audit->log('shift.assigned', $shift, [], ['user_id' => $userId],
            "Assigned shift {$shift->name} to user #{$userId}");
    }

    public function assignToDepartment(Shift $shift, Department $department, bool $overwriteUserShifts = false): void
    {
        $department->shift_id = $shift->id;
        $department->save();

        if ($overwriteUserShifts) {
            $department->employees()->update(['shift_id' => $shift->id]);
        }

        $this->audit->log('shift.assigned', $shift, [], [
            'department_id' => $department->id,
            'overwrite_users' => $overwriteUserShifts,
        ], "Assigned shift {$shift->name} to department {$department->name}");
    }
}
