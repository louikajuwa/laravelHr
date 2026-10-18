<?php

namespace Tests\Feature\Payroll;

use App\Models\SalaryStructure;
use App\Models\User;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalaryStructureVersioningTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function payload(User $u, array $o = []): array
    {
        return array_merge([
            'user_id' => $u->id, 'basic_salary' => 30000, 'pay_frequency' => 'monthly',
            'working_days_per_month' => 22, 'overtime_rate_multiplier' => 1.25,
            'late_deduction_per_minute' => 0, 'absence_deduction_mode' => 'daily_rate',
            'perfect_attendance_bonus' => 0, 'effective_from' => '2026-03-01',
        ], $o);
    }

    public function test_new_structure_closes_previous_and_history_uses_old(): void
    {
        Carbon::setTestNow('2026-06-15');
        $u = User::factory()->create(['hire_date' => '2025-01-01']);
        $old = SalaryStructure::factory()->create(['user_id' => $u->id, 'basic_salary' => 22000, 'effective_from' => '2025-01-01']);

        $this->actingAs($this->admin())->post(route('hr.salary-structures.store'), $this->payload($u))->assertSessionHasNoErrors();

        $old->refresh();
        $this->assertSame('2026-02-28', $old->effective_to->toDateString());
        $this->assertFalse($old->is_active);

        $feb = app(PayrollService::class)->generate($u, Carbon::parse('2026-02-01'), Carbon::parse('2026-02-28'), $u);
        $this->assertEquals(22000, $feb->basic);

        $apr = app(PayrollService::class)->generate($u, Carbon::parse('2026-04-01'), Carbon::parse('2026-04-30'), $u);
        $this->assertEquals(30000, $apr->basic);
    }

    public function test_update_cannot_change_user(): void
    {
        $u = User::factory()->create();
        $other = User::factory()->create();
        $s = SalaryStructure::factory()->create(['user_id' => $u->id]);

        $this->actingAs($this->admin())->put(route('hr.salary-structures.update', $s), $this->payload($other))
            ->assertSessionHasErrors('user_id');

        $this->assertSame($u->id, $s->fresh()->user_id);
    }
}
