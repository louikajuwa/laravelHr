<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private function record(array $over = []): Attendance
    {
        $emp = $this->employee();

        return Attendance::factory()->create(array_merge([
            'user_id' => $emp->id, 'date' => '2026-10-19', 'time_in' => '2026-10-19 09:00:00',
            'shift_id' => Shift::factory()->create()->id, 'status' => 'present',
        ], $over));
    }

    public function test_holiday_status_is_kept_and_unchanged_fields_make_no_rows(): void
    {
        $admin = $this->admin();
        $a = $this->record();

        $this->actingAs($admin)->put(route('hr.attendance.update', $a), [
            'time_in' => '2026-10-19T09:00', 'time_out' => '', 'status' => 'holiday', 'remarks' => '', 'reason' => 'public holiday',
        ])->assertSessionHasNoErrors();

        $a->refresh();
        $this->assertSame('holiday', $a->status);
        $this->assertSame(['status'], $a->corrections()->pluck('field')->all());
    }

    public function test_present_is_recomputed_as_late(): void
    {
        $admin = $this->admin();
        $a = $this->record(['time_in' => null, 'status' => 'absent']);

        $this->actingAs($admin)->put(route('hr.attendance.update', $a), [
            'time_in' => '2026-10-19T10:00', 'status' => 'present', 'reason' => 'forgot',
        ])->assertSessionHasNoErrors();

        $this->assertSame('late', $a->fresh()->status);
    }

    public function test_time_in_on_other_date_is_rejected(): void
    {
        $admin = $this->admin();
        $a = $this->record();

        $this->actingAs($admin)->put(route('hr.attendance.update', $a), [
            'time_in' => '2026-10-21T09:00', 'status' => 'present', 'reason' => 'x',
        ])->assertSessionHasErrors('time_in');
    }

    public function test_time_out_next_day_only_for_overnight_shift(): void
    {
        $admin = $this->admin();
        $a = $this->record();

        $this->actingAs($admin)->put(route('hr.attendance.update', $a), [
            'time_in' => '2026-10-19T09:00', 'time_out' => '2026-10-20T01:00', 'status' => 'present', 'reason' => 'x',
        ])->assertSessionHasErrors('time_out');

        $night = $this->record(['shift_id' => Shift::factory()->overnight()->create()->id, 'time_in' => '2026-10-19 22:00:00']);
        $this->actingAs($admin)->put(route('hr.attendance.update', $night), [
            'time_in' => '2026-10-19T22:00', 'time_out' => '2026-10-20T06:00', 'status' => 'present', 'reason' => 'x',
        ])->assertSessionHasNoErrors();
    }

    public function test_time_out_without_any_time_in_is_rejected(): void
    {
        $admin = $this->admin();
        $a = $this->record(['time_in' => null, 'status' => 'absent']);

        $this->actingAs($admin)->put(route('hr.attendance.update', $a), [
            'time_out' => '2026-10-19T17:00', 'status' => 'present', 'reason' => 'x',
        ])->assertSessionHasErrors('time_out');
    }
}
