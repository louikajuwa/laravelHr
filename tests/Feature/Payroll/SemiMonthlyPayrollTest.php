<?php

namespace Tests\Feature\Payroll;

use App\Models\Payroll;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SemiMonthlyPayrollTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $freq): User
    {
        $u = User::factory()->create();
        SalaryStructure::factory()->create(['user_id' => $u->id, 'pay_frequency' => $freq, 'basic_salary' => 22000, 'perfect_attendance_bonus' => 0]);

        return $u;
    }

    private function gen(User $u, string $s, string $e): Payroll
    {
        return app(PayrollService::class)->generate($u, Carbon::parse($s), Carbon::parse($e), $u);
    }

    public function test_validation_accepts_halves_and_rejects_other_ranges(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('hr.payroll.generate'), ['period_start' => '2026-03-01', 'period_end' => '2026-03-15'])->assertSessionHasNoErrors();
        $this->post(route('hr.payroll.generate'), ['period_start' => '2026-03-16', 'period_end' => '2026-03-31'])->assertSessionHasNoErrors();
        $this->post(route('hr.payroll.generate'), ['period_start' => '2026-03-03', 'period_end' => '2026-03-20'])->assertSessionHasErrors('period_end');
        $this->post(route('hr.payroll.generate'), ['period_start' => '2026-03-16', 'period_end' => '2026-04-15'])->assertSessionHasErrors('period_end');
    }

    public function test_semi_monthly_generates_half_basic_with_label(): void
    {
        $u = $this->user('biweekly');
        $a = $this->gen($u, '2026-01-01', '2026-01-15');
        $b = $this->gen($u, '2026-01-16', '2026-01-31');

        $this->assertEquals(11000, $a->basic);
        $this->assertSame('Jan 2026 (1–15)', $a->label);
        $this->assertEquals(11000, $b->basic);
        $this->assertSame('Jan 2026 (16–31)', $b->label);
    }

    public function test_monthly_user_with_half_period_is_rejected(): void
    {
        $u = $this->user('monthly');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is paid monthly; generate a full-month period.');
        $this->gen($u, '2026-01-01', '2026-01-15');
    }

    public function test_semi_monthly_user_with_full_month_is_rejected(): void
    {
        $u = $this->user('biweekly');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is paid semi-monthly; generate 1–15 or 16–end.');
        $this->gen($u, '2026-01-01', '2026-01-31');
    }

    public function test_batch_generates_matching_and_skips_others(): void
    {
        $m = $this->user('monthly');
        $s = $this->user('biweekly');

        $r = app(PayrollService::class)->generateBatch(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-15'), $s);

        $this->assertSame(1, $r['generated']);
        $this->assertSame(1, $r['skipped']);
        $this->assertSame(1, $r['mismatched']);
        $this->assertCount(1, $r['skipped_reasons']);
        $this->assertSame([], $r['failed']);
        $this->assertDatabaseHas('payrolls', ['user_id' => $s->id]);
        $this->assertDatabaseMissing('payrolls', ['user_id' => $m->id]);
    }

    public function test_controller_flash_mentions_frequency_skips(): void
    {
        $this->user('monthly');
        $this->user('biweekly');

        $this->actingAs($this->admin())
            ->post(route('hr.payroll.generate'), ['period_start' => '2026-02-01', 'period_end' => '2026-02-15'])
            ->assertSessionHas('status', 'Payroll: 1 generated, 1 skipped (1 on a different pay frequency).');
    }

    public function test_halves_do_not_overlap_each_other(): void
    {
        $u = $this->user('biweekly');
        $this->gen($u, '2026-02-01', '2026-02-15');
        $this->gen($u, '2026-02-16', '2026-02-28');

        $this->assertSame(2, Payroll::where('user_id', $u->id)->count());

        $this->expectException(RuntimeException::class);
        $this->gen($u, '2026-02-01', '2026-02-15');
    }
}
