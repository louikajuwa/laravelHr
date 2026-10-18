<?php

namespace Tests\Feature\Leave;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveTypeDeleteGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_delete_type_with_requests(): void
    {
        $admin = $this->admin();
        $type = LeaveType::factory()->create();
        LeaveRequest::factory()->create(['leave_type_id' => $type->id, 'user_id' => $this->employee()->id]);

        $this->actingAs($admin)->delete(route('hr.leave-types.destroy', $type))->assertSessionHasErrors('leave_type');

        $this->assertDatabaseHas('leave_types', ['id' => $type->id]);
        $this->assertSame(1, LeaveRequest::count());
    }

    public function test_cannot_delete_type_with_balances(): void
    {
        $admin = $this->admin();
        $type = LeaveType::factory()->create();
        $emp = $this->employee();
        $emp->leaveBalances()->create(['leave_type_id' => $type->id, 'year' => now()->year, 'allocated' => 5]);

        $this->actingAs($admin)->delete(route('hr.leave-types.destroy', $type))->assertSessionHasErrors('leave_type');
        $this->assertDatabaseHas('leave_types', ['id' => $type->id]);
    }

    public function test_unused_type_can_be_deleted(): void
    {
        $admin = $this->admin();
        $type = LeaveType::factory()->create();

        $this->actingAs($admin)->delete(route('hr.leave-types.destroy', $type))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('leave_types', ['id' => $type->id]);
    }
}
