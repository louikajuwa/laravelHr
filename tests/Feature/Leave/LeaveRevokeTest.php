<?php

namespace Tests\Feature\Leave;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRevokeTest extends TestCase
{
    use RefreshDatabase;

    private function approved(User $emp, ?LeaveType $type = null, int $from = -1, int $to = 1, string $status = 'approved'): LeaveRequest
    {
        $type ??= LeaveType::factory()->create();
        LeaveBalance::updateOrCreate(
            ['user_id' => $emp->id, 'leave_type_id' => $type->id, 'year' => now()->addDays($from)->year],
            ['allocated' => 10, 'used' => $status === 'approved' ? 3 : 0, 'pending' => 0],
        );

        return LeaveRequest::factory()->create([
            'user_id' => $emp->id, 'leave_type_id' => $type->id,
            'start_date' => now()->addDays($from)->toDateString(),
            'end_date' => now()->addDays($to)->toDateString(),
            'days' => 3, 'status' => $status, 'decision_note' => 'ok',
        ]);
    }

    private function att(User $emp, int $offset, array $attrs = []): Attendance
    {
        return Attendance::factory()->create(array_merge([
            'user_id' => $emp->id, 'date' => now()->addDays($offset)->toDateString(), 'status' => 'on_leave',
        ], $attrs));
    }

    public function test_revoke_refunds_balance_and_sets_fields(): void
    {
        $admin = $this->admin();
        $emp = $this->employee();
        $leave = $this->approved($emp);

        $this->actingAs($admin)->post(route('hr.leave.revoke', $leave), ['note' => 'Project deadline'])
            ->assertSessionHasNoErrors();

        $leave->refresh();
        $this->assertSame('revoked', $leave->status);
        $this->assertNotNull($leave->revoked_at);
        $this->assertSame($admin->id, $leave->revoked_by);
        $this->assertSame('Revoked: Project deadline', $leave->decision_note);
        $this->assertEquals(0, LeaveBalance::where('user_id', $emp->id)->first()->used);
        $this->assertTrue(AuditLog::where('action', 'leave.revoked')->exists());
    }

    public function test_past_on_leave_becomes_absent_and_future_deleted(): void
    {
        $admin = $this->admin();
        $emp = $this->employee();
        $leave = $this->approved($emp, null, -1, 1);
        $past = $this->att($emp, -1);
        $today = $this->att($emp, 0);
        $future = $this->att($emp, 1);

        $this->actingAs($admin)->post(route('hr.leave.revoke', $leave))->assertSessionHasNoErrors();

        $this->assertSame('absent', $past->fresh()->status);
        $this->assertSame('Leave revoked', $past->fresh()->remarks);
        $this->assertNull(Attendance::find($today->id));
        $this->assertNull(Attendance::find($future->id));
    }

    public function test_worked_days_are_untouched(): void
    {
        $admin = $this->admin();
        $emp = $this->employee();
        $leave = $this->approved($emp, null, -2, -1);
        $worked = $this->att($emp, -2, ['time_in' => now()->subDays(2)]);

        $this->actingAs($admin)->post(route('hr.leave.revoke', $leave))->assertSessionHasNoErrors();

        $this->assertSame('on_leave', $worked->fresh()->status);
    }

    public function test_blocked_by_finalized_or_paid_payroll(): void
    {
        foreach (['finalized', 'paid'] as $status) {
            $admin = $this->admin();
            $emp = $this->employee();
            $leave = $this->approved($emp);
            Payroll::factory()->status($status)->create([
                'user_id' => $emp->id,
                'period_start' => now()->subDays(10)->toDateString(),
                'period_end' => now()->addDays(10)->toDateString(),
            ]);

            $this->actingAs($admin)->post(route('hr.leave.revoke', $leave))->assertSessionHasErrors('leave');
            $this->assertSame('approved', $leave->fresh()->status);
            $this->assertEquals(3, LeaveBalance::where('user_id', $emp->id)->first()->used);
        }
    }

    public function test_overlapping_draft_payroll_is_discarded(): void
    {
        $admin = $this->admin();
        $emp = $this->employee();
        $leave = $this->approved($emp);
        $draft = Payroll::factory()->create([
            'user_id' => $emp->id,
            'period_start' => now()->subDays(10)->toDateString(),
            'period_end' => now()->addDays(10)->toDateString(),
        ]);
        $other = Payroll::factory()->create([
            'user_id' => $emp->id,
            'period_start' => now()->addDays(40)->toDateString(),
            'period_end' => now()->addDays(70)->toDateString(),
        ]);

        $this->actingAs($admin)->post(route('hr.leave.revoke', $leave))
            ->assertSessionHasNoErrors()->assertSessionHas('status');

        $this->assertNull(Payroll::find($draft->id));
        $this->assertNotNull(Payroll::find($other->id));
        $this->assertTrue(AuditLog::where('action', 'payroll.draft_discarded')->exists());
    }

    public function test_pending_and_rejected_cannot_be_revoked(): void
    {
        $admin = $this->admin();
        $emp = $this->employee();
        foreach (['pending', 'rejected'] as $status) {
            $leave = $this->approved($emp, null, 20, 22, $status);
            $this->actingAs($admin)->post(route('hr.leave.revoke', $leave))->assertSessionHasErrors('leave');
            $this->assertSame($status, $leave->fresh()->status);
        }
    }

    public function test_admin_cannot_revoke_own_leave(): void
    {
        $admin = $this->admin();
        $leave = $this->approved($admin);

        $this->actingAs($admin)->post(route('hr.leave.revoke', $leave))->assertSessionHasErrors('leave');
        $this->assertSame('approved', $leave->fresh()->status);
    }

    public function test_employee_gets_403(): void
    {
        $emp = $this->employee();
        $leave = $this->approved($this->employee());

        $this->actingAs($emp)->post(route('hr.leave.revoke', $leave))->assertForbidden();
    }
}
