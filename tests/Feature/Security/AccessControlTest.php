<?php

namespace Tests\Feature\Security;

use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function payroll(User $user, string $status): Payroll
    {
        return Payroll::create([
            'user_id' => $user->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'status' => $status,
        ]);
    }

    public function test_guest_redirected_to_login(): void
    {
        $this->get('/hr/dashboard')->assertRedirect(route('login'));
        $this->get('/me/payroll')->assertRedirect(route('login'));
    }

    public function test_employee_cannot_open_hr_dashboard(): void
    {
        $this->actingAs($this->employee())->get('/hr/dashboard')->assertForbidden();
    }

    public function test_employee_cannot_view_another_employees_payroll(): void
    {
        $owner = $this->employee();
        $other = $this->employee();
        $p = $this->payroll($owner, 'finalized');

        $this->actingAs($other)->get(route('me.payroll.show', $p))->assertNotFound();
        $this->actingAs($other)->get(route('me.payroll.payslip', $p))->assertNotFound();
    }

    public function test_draft_payroll_is_404_for_owner(): void
    {
        $owner = $this->employee();
        $p = $this->payroll($owner, 'draft');

        $this->actingAs($owner)->get(route('me.payroll.show', $p))->assertNotFound();
        $this->actingAs($owner)->get(route('me.payroll.payslip', $p))->assertNotFound();
    }

    public function test_finalized_payroll_visible_to_owner(): void
    {
        $owner = $this->employee();
        $p = $this->payroll($owner, 'finalized');

        $this->actingAs($owner)->get(route('me.payroll.show', $p))->assertOk();
    }
}
