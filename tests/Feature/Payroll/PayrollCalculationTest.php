<?php

namespace Tests\Feature\Payroll;

use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function employeeWithStructure(array $userAttrs = []): User
    {
        $u = User::factory()->create(array_merge(['hire_date' => '2025-01-01'], $userAttrs));
        SalaryStructure::factory()->create(['user_id' => $u->id]);

        return $u;
    }

    private function fillDays(User $u, string $from, string $to, array $overrides = []): void
    {
        for ($d = Carbon::parse($from); $d->lte(Carbon::parse($to)); $d->addDay()) {
            Attendance::create([
                'user_id' => $u->id,
                'date' => $d->toDateString(),
                'status' => $overrides[$d->toDateString()] ?? 'present',
            ]);
        }
    }

    private function gen(User $u, string $start, string $end): Payroll
    {
        return app(PayrollService::class)->generate($u, Carbon::parse($start), Carbon::parse($end), User::factory()->create());
    }

    public function test_perfect_attendance_gets_bonus(): void
    {
        Carbon::setTestNow('2026-03-15');
        $u = $this->employeeWithStructure();
        $this->fillDays($u, '2026-01-01', '2026-01-31');

        $p = $this->gen($u, '2026-01-01', '2026-01-31');

        $this->assertEquals(500, $p->bonuses);
        $this->assertEquals(0, $p->meta['days_absent']);
    }

    public function test_holiday_is_not_absent(): void
    {
        Carbon::setTestNow('2026-03-15');
        $u = $this->employeeWithStructure();
        $this->fillDays($u, '2026-01-01', '2026-01-31', ['2026-01-01' => 'holiday']);

        $p = $this->gen($u, '2026-01-01', '2026-01-31');

        $this->assertEquals(0, $p->meta['days_absent']);
        $this->assertEquals(500, $p->bonuses);
    }

    public function test_half_day_counts_half_absent_and_loses_bonus(): void
    {
        Carbon::setTestNow('2026-03-15');
        $u = $this->employeeWithStructure();
        $this->fillDays($u, '2026-01-01', '2026-01-31', ['2026-01-10' => 'half_day']);

        $p = $this->gen($u, '2026-01-01', '2026-01-31');

        $this->assertEquals(0.5, $p->meta['days_absent']);
        $this->assertEquals(0, $p->bonuses);
        $this->assertEquals(500.0, (float) $p->absence_deduction); // 0.5 * (22000 / 22)
    }

    public function test_days_before_hire_date_are_not_absent(): void
    {
        Carbon::setTestNow('2026-03-15');
        $u = $this->employeeWithStructure(['hire_date' => '2026-01-21']);
        $this->fillDays($u, '2026-01-21', '2026-01-31');

        $p = $this->gen($u, '2026-01-01', '2026-01-31');

        $this->assertEquals(0, $p->meta['days_absent']);
    }

    public function test_future_days_are_not_absent(): void
    {
        Carbon::setTestNow('2026-02-10 12:00:00');
        $u = $this->employeeWithStructure();
        $this->fillDays($u, '2026-02-01', '2026-02-10');

        $p = $this->gen($u, '2026-02-01', '2026-02-28');

        $this->assertEquals(0, $p->meta['days_absent']);
    }

    public function test_absent_day_loses_bonus_and_is_deducted(): void
    {
        Carbon::setTestNow('2026-03-15');
        $u = $this->employeeWithStructure();
        $this->fillDays($u, '2026-01-01', '2026-01-31', ['2026-01-05' => 'absent']);

        $p = $this->gen($u, '2026-01-01', '2026-01-31');

        $this->assertEquals(1, $p->meta['days_absent']);
        $this->assertEquals(0, $p->bonuses);
        $this->assertEquals(1000.0, (float) $p->absence_deduction);
    }
}
