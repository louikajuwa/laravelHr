<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeAttendanceMonthParamTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_month_falls_back(): void
    {
        $emp = $this->employee();

        foreach (['abc', '2026-13', '2026-1-1', 'x-y', '9999999-01'] as $m) {
            $this->actingAs($emp)->get(route('me.attendance.index', ['month' => $m]))->assertOk();
            $this->actingAs($emp)->getJson(route('me.attendance.index', ['month' => $m]))->assertOk();
        }
        $this->actingAs($emp)->get(route('me.attendance.index', ['month' => ['a']]))->assertOk();
        $this->actingAs($emp)->get(route('me.attendance.index', ['month' => '2026-02']))->assertOk();
    }
}
