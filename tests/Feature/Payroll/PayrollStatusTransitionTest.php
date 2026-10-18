<?php

namespace Tests\Feature\Payroll;

use App\Models\AuditLog;
use App\Models\Payroll;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_cannot_be_paid_directly(): void
    {
        $p = Payroll::factory()->create();

        $this->actingAs($this->admin())->post(route('hr.payroll.pay', $p))->assertSessionHasErrors('payroll');

        $this->assertSame('draft', $p->fresh()->status);
    }

    public function test_finalize_twice_is_rejected(): void
    {
        $p = Payroll::factory()->status('finalized')->create();

        $this->actingAs($this->admin())->post(route('hr.payroll.finalize', $p))->assertSessionHasErrors('payroll');
    }

    public function test_paid_cannot_be_finalized_again(): void
    {
        $p = Payroll::factory()->status('paid')->create();

        $this->actingAs($this->admin())->post(route('hr.payroll.finalize', $p))->assertSessionHasErrors('payroll');

        $this->assertSame('paid', $p->fresh()->status);
    }

    public function test_happy_path_draft_finalized_paid(): void
    {
        $p = Payroll::factory()->create();
        $this->actingAs($this->admin());

        $this->post(route('hr.payroll.finalize', $p))->assertSessionHasNoErrors();
        $this->assertSame('finalized', $p->fresh()->status);

        $this->post(route('hr.payroll.pay', $p))->assertSessionHasNoErrors();
        $fresh = $p->fresh();
        $this->assertSame('paid', $fresh->status);
        $this->assertNotNull($fresh->paid_at);

        $paid = AuditLog::where('action', 'payroll.paid')->first();
        $this->assertSame('finalized', $paid->old_values['status']);
    }
}
