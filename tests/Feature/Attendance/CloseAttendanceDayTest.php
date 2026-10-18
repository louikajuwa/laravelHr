<?php

namespace Tests\Feature\Attendance;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloseAttendanceDayTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_closes_yesterday(): void
    {
        $emp = $this->employee();
        $this->travelTo('2026-10-20 00:05:00');

        $this->artisan('attendance:close-day')->assertSuccessful();

        $this->assertDatabaseHas('attendances', ['user_id' => $emp->id, 'date' => '2026-10-19 00:00:00', 'status' => 'absent']);
        $this->assertDatabaseMissing('attendances', ['user_id' => $emp->id, 'date' => '2026-10-20 00:00:00']);
    }

    public function test_explicit_date_and_leave(): void
    {
        $emp = $this->employee();
        $onLeave = $this->employee();
        LeaveRequest::factory()->create([
            'user_id' => $onLeave->id, 'status' => 'approved', 'start_date' => '2026-10-10', 'end_date' => '2026-10-12',
        ]);

        $this->artisan('attendance:close-day', ['date' => '2026-10-11'])->assertSuccessful();
        $this->artisan('attendance:close-day', ['--date' => '2026-10-11'])->assertSuccessful();

        $this->assertDatabaseHas('attendances', ['user_id' => $emp->id, 'date' => '2026-10-11 00:00:00', 'status' => 'absent']);
        $this->assertDatabaseHas('attendances', ['user_id' => $onLeave->id, 'date' => '2026-10-11 00:00:00', 'status' => 'on_leave']);
    }
}
