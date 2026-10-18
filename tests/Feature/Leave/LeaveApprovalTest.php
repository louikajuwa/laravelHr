<?php

namespace Tests\Feature\Leave;

use App\Models\Attendance;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function submit(User $emp, LeaveType $type, int $from = 3, int $to = 4): LeaveRequest
    {
        $this->actingAs($emp)->post(route('me.leave.store'), [
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays($from)->toDateString(),
            'end_date' => now()->addDays($to)->toDateString(),
        ])->assertSessionHasNoErrors();

        return LeaveRequest::where('user_id', $emp->id)->latest('id')->firstOrFail();
    }

    private function balance(User $emp, LeaveType $type): LeaveBalance
    {
        return LeaveBalance::where(['user_id' => $emp->id, 'leave_type_id' => $type->id])->firstOrFail();
    }

    public function test_approve_updates_balance_once_and_second_approve_rejected(): void
    {
        $admin = $this->admin();
        $emp = $this->employee();
        $type = LeaveType::factory()->create();
        $leave = $this->submit($emp, $type);

        $this->assertEquals(2, $this->balance($emp, $type)->pending);

        $this->actingAs($admin)->post(route('hr.leave.approve', $leave))->assertSessionHasNoErrors();
        $b = $this->balance($emp, $type);
        $this->assertEquals(2, $b->used);
        $this->assertEquals(0, $b->pending);

        $this->actingAs($admin)->post(route('hr.leave.approve', $leave))->assertSessionHasErrors('leave');
        $b = $this->balance($emp, $type);
        $this->assertEquals(2, $b->used);
        $this->assertEquals(0, $b->pending);
        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertDatabaseHas('attendances', ['user_id' => $emp->id, 'status' => 'on_leave']);
    }

    public function test_admin_cannot_decide_own_leave(): void
    {
        $admin = $this->admin();
        $type = LeaveType::factory()->create();
        $leave = LeaveRequest::factory()->create(['user_id' => $admin->id, 'leave_type_id' => $type->id]);

        $this->actingAs($admin)->post(route('hr.leave.approve', $leave))->assertRedirect()->assertSessionHasErrors('leave');
        $this->actingAs($admin)->post(route('hr.leave.reject', $leave))->assertRedirect()->assertSessionHasErrors('leave');
        $this->assertSame('pending', $leave->fresh()->status);
    }

    public function test_reject_releases_pending(): void
    {
        $admin = $this->admin();
        $emp = $this->employee();
        $type = LeaveType::factory()->create();
        $leave = $this->submit($emp, $type);

        $this->actingAs($admin)->post(route('hr.leave.reject', $leave), ['decision_note' => 'no'])->assertSessionHasNoErrors();

        $this->assertSame('rejected', $leave->fresh()->status);
        $this->assertEquals(0, $this->balance($emp, $type)->pending);
        $this->assertEquals(0, $this->balance($emp, $type)->used);
    }

    public function test_decision_note_too_long_is_rejected(): void
    {
        $admin = $this->admin();
        $emp = $this->employee();
        $leave = $this->submit($emp, LeaveType::factory()->create());

        $this->actingAs($admin)->post(route('hr.leave.approve', $leave), ['decision_note' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('decision_note');
        $this->assertSame('pending', $leave->fresh()->status);
    }

    public function test_cancel_only_pending(): void
    {
        $admin = $this->admin();
        $emp = $this->employee();
        $type = LeaveType::factory()->create();
        $leave = $this->submit($emp, $type);

        $this->actingAs($emp)->post(route('me.leave.cancel', $leave))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $leave->fresh()->status);
        $this->assertEquals(0, $this->balance($emp, $type)->pending);

        $this->actingAs($emp)->post(route('me.leave.cancel', $leave))->assertSessionHasErrors('leave');
        $this->assertEquals(0, $this->balance($emp, $type)->pending);

        $l2 = $this->submit($emp, $type, 10, 10);
        $this->actingAs($admin)->post(route('hr.leave.approve', $l2));
        $this->actingAs($emp)->post(route('me.leave.cancel', $l2))->assertSessionHasErrors('leave');
        $this->assertSame('approved', $l2->fresh()->status);
    }

    public function test_cannot_cancel_another_users_leave(): void
    {
        $emp = $this->employee();
        $other = $this->employee();
        $leave = $this->submit($emp, LeaveType::factory()->create());

        $this->actingAs($other)->post(route('me.leave.cancel', $leave))->assertForbidden();
    }

    public function test_approve_does_not_overwrite_present_attendance(): void
    {
        $admin = $this->admin();
        $emp = $this->employee();
        $type = LeaveType::factory()->create();
        $leave = $this->submit($emp, $type, 3, 4);

        $worked = Attendance::factory()->create([
            'user_id' => $emp->id, 'date' => now()->addDays(3)->toDateString(),
            'time_in' => now()->addDays(3)->setTime(9, 0), 'status' => 'present',
        ]);

        $this->actingAs($admin)->post(route('hr.leave.approve', $leave))->assertSessionHasNoErrors();

        $this->assertSame('present', $worked->fresh()->status);
        $this->assertSame('on_leave', Attendance::where('user_id', $emp->id)->whereDate('date', now()->addDays(4)->toDateString())->value('status'));
    }
}
