<?php

namespace Tests\Feature\Timezone;

use App\Models\Attendance;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BusinessTimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['hr.timezone' => 'Asia/Manila']);
    }

    public function test_clock_in_uses_company_local_date_and_stores_utc(): void
    {
        $emp = $this->employee();
        $this->travelTo('2026-10-18 23:30:00'); // 07:30 on Oct 19 in Manila

        $this->actingAs($emp)->postJson(route('me.attendance.clock-in'))->assertOk();

        $a = Attendance::firstOrFail();
        $this->assertSame('2026-10-19', $a->date->toDateString());
        $this->assertSame('2026-10-18 23:30:00', DB::table('attendances')->value('time_in'));
    }

    public function test_lateness_uses_shift_start_in_company_timezone(): void
    {
        $shift = Shift::factory()->create(['start_time' => '08:00:00', 'grace_period_minutes' => 5]);
        $emp = $this->employee(['shift_id' => $shift->id]);
        $this->travelTo('2026-10-19 00:10:00'); // 08:10 Manila

        $this->actingAs($emp)->postJson(route('me.attendance.clock-in'))->assertOk();

        $a = Attendance::firstOrFail();
        $this->assertTrue($a->is_late);
        $this->assertSame(10, $a->late_minutes);
        $this->assertSame('2026-10-19', $a->date->toDateString());
    }

    public function test_close_day_default_is_local_yesterday(): void
    {
        $emp = $this->employee();
        $this->travelTo('2026-10-18 17:00:00'); // 01:00 Oct 19 Manila; UTC date is still Oct 18

        $this->artisan('attendance:close-day')->assertSuccessful();

        $this->assertDatabaseHas('attendances', ['user_id' => $emp->id, 'date' => '2026-10-18 00:00:00', 'status' => 'absent']);
        $this->assertDatabaseMissing('attendances', ['user_id' => $emp->id, 'date' => '2026-10-17 00:00:00']);
    }

    public function test_correction_input_is_company_local_and_stored_as_utc(): void
    {
        $admin = $this->admin();
        $emp = $this->employee();
        $a = Attendance::factory()->create([
            'user_id' => $emp->id, 'date' => '2026-10-19', 'time_in' => null, 'status' => 'absent',
            'shift_id' => Shift::factory()->create(['start_time' => '08:00:00'])->id,
        ]);

        $this->actingAs($admin)->put(route('hr.attendance.update', $a), [
            'time_in' => '2026-10-19 08:00', 'status' => 'present', 'reason' => 'forgot',
        ])->assertSessionHasNoErrors();

        $this->assertSame('2026-10-19 00:00:00', DB::table('attendances')->where('id', $a->id)->value('time_in'));
        $this->assertSame('present', $a->fresh()->status);
    }
}
