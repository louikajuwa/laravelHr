<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceClockTest extends TestCase
{
    use RefreshDatabase;

    public function test_clock_in_and_out_same_day(): void
    {
        $shift = Shift::factory()->create();
        $emp = $this->employee(['shift_id' => $shift->id]);

        $this->travelTo('2026-10-19 09:05:00');
        $this->actingAs($emp)->postJson(route('me.attendance.clock-in'))->assertOk()->assertJson(['ok' => true, 'is_late' => false]);
        $this->actingAs($emp)->postJson(route('me.attendance.clock-in'))->assertStatus(422);

        $this->travelTo('2026-10-19 17:30:00');
        $this->actingAs($emp)->postJson(route('me.attendance.clock-out'))->assertOk()->assertJson(['ok' => true]);
        $this->actingAs($emp)->postJson(route('me.attendance.clock-out'))->assertStatus(422);

        $a = Attendance::firstOrFail();
        $this->assertSame('present', $a->status);
        $this->assertSame(445, $a->worked_minutes); // 8h25m minus 60m break
    }

    public function test_late_clock_in(): void
    {
        $emp = $this->employee(['shift_id' => Shift::factory()->create()->id]);
        $this->travelTo('2026-10-19 09:30:00');
        $this->actingAs($emp)->postJson(route('me.attendance.clock-in'))->assertOk()->assertJson(['is_late' => true]);
        $this->assertSame('late', Attendance::firstOrFail()->status);
    }

    public function test_clock_out_without_clock_in_fails(): void
    {
        $emp = $this->employee(['shift_id' => Shift::factory()->create()->id]);
        $this->travelTo('2026-10-19 17:00:00');
        $this->actingAs($emp)->postJson(route('me.attendance.clock-out'))->assertStatus(422);
    }

    public function test_overnight_shift_clock_out_next_day_closes_previous_row(): void
    {
        $emp = $this->employee(['shift_id' => Shift::factory()->overnight()->create()->id]);

        $this->travelTo('2026-10-19 22:05:00');
        $this->actingAs($emp)->postJson(route('me.attendance.clock-in'))->assertOk();

        $this->travelTo('2026-10-20 06:10:00');
        $this->actingAs($emp)->postJson(route('me.attendance.clock-out'))->assertOk()->assertJson(['ok' => true]);

        $this->assertSame(1, Attendance::count());
        $a = Attendance::firstOrFail();
        $this->assertSame('2026-10-19', $a->date->toDateString());
        $this->assertNotNull($a->time_out);
        $this->assertSame(8 * 60 + 5 - 60, $a->worked_minutes);
    }

    public function test_day_shift_does_not_close_previous_open_row(): void
    {
        $emp = $this->employee(['shift_id' => Shift::factory()->create()->id]);
        Attendance::factory()->create([
            'user_id' => $emp->id, 'date' => '2026-10-19', 'time_in' => '2026-10-19 09:00:00',
        ]);

        $this->travelTo('2026-10-20 08:00:00');
        $this->actingAs($emp)->postJson(route('me.attendance.clock-out'))->assertStatus(422);
    }
}
