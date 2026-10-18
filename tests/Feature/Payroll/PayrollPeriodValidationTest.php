<?php

namespace Tests\Feature\Payroll;

use App\Models\SalaryStructure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollPeriodValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_month_is_rejected(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('hr.payroll.generate'), ['period_start' => '2026-01-05', 'period_end' => '2026-01-31'])
            ->assertSessionHasErrors('period_end');
        $this->post(route('hr.payroll.generate'), ['period_start' => '2026-01-03', 'period_end' => '2026-01-20'])
            ->assertSessionHasErrors('period_end');
        $this->post(route('hr.payroll.generate'), ['period_start' => '2026-01-01', 'period_end' => '2026-02-28'])
            ->assertSessionHasErrors('period_end');
    }

    public function test_full_calendar_month_is_accepted(): void
    {
        $emp = User::factory()->create();
        SalaryStructure::factory()->create(['user_id' => $emp->id]);

        $this->actingAs($this->admin())
            ->post(route('hr.payroll.generate'), ['period_start' => '2026-02-01', 'period_end' => '2026-02-28'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payrolls', ['user_id' => $emp->id, 'period_start' => '2026-02-01', 'period_end' => '2026-02-28']);
    }
}
