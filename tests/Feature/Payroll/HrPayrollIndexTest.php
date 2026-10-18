<?php

namespace Tests\Feature\Payroll;

use App\Models\Payroll;
use App\Models\SalaryStructure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrPayrollIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_with_soft_deleted_employee_returns_200(): void
    {
        $u = User::factory()->create();
        Payroll::factory()->create(['user_id' => $u->id]);
        SalaryStructure::factory()->create(['user_id' => $u->id]);
        $u->delete();
        $this->actingAs($this->admin());

        $this->get(route('hr.payroll.index'))->assertOk();
        $this->getJson(route('hr.payroll.index'))->assertOk();
        $this->getJson(route('hr.salary-structures.index'))->assertOk();
    }

    public function test_invalid_period_start_is_validation_error_not_500(): void
    {
        $this->actingAs($this->admin())->getJson(route('hr.payroll.index', ['period_start' => 'garbage']))->assertStatus(422);
    }
}
