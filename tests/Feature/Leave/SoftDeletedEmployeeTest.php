<?php

namespace Tests\Feature\Leave;

use App\Models\Attendance;
use App\Models\LateArrivalReport;
use App\Models\LeaveRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeletedEmployeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_pages_survive_soft_deleted_employee(): void
    {
        $admin = $this->admin();
        $emp = $this->employee(['name' => 'Gone Person']);

        $leave = LeaveRequest::factory()->create(['user_id' => $emp->id, 'approver_id' => $emp->id]);
        $att = Attendance::factory()->create(['user_id' => $emp->id, 'date' => now()->toDateString()]);
        LateArrivalReport::create([
            'user_id' => $emp->id, 'attendance_id' => $att->id, 'date' => now()->toDateString(),
            'late_minutes' => 5, 'reason' => 'bus', 'status' => 'pending', 'reviewed_by' => $emp->id,
        ]);

        $emp->delete();

        foreach (['hr.leave.index', 'hr.attendance.index', 'hr.late-arrivals.index'] as $name) {
            $this->actingAs($admin)->get(route($name))->assertOk();
            $this->actingAs($admin)->getJson(route($name))->assertOk()->assertJsonCount(1, 'data');
        }

        $this->actingAs($admin)->get(route('hr.attendance.edit', $att))->assertOk();
        $this->assertSame('Gone Person', $leave->fresh()->user->name);
    }
}
