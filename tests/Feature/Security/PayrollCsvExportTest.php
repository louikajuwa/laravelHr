<?php

namespace Tests\Feature\Security;

use App\Models\Payroll;
use App\Services\PayrollExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_formula_cells_are_neutralized_in_export(): void
    {
        $admin = $this->admin();
        $emp = $this->employee(['name' => '=HYPERLINK("http://evil")', 'employee_code' => '@EMP1']);
        Payroll::create([
            'user_id' => $emp->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'status' => 'finalized',
            'net_pay' => 1234.5,
        ]);

        $csv = $this->actingAs($admin)->get(route('hr.payroll.export'))->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'@EMP1", $csv);
        $this->assertStringNotContainsString(',=HYPERLINK', $csv);
        $this->assertStringContainsString('1234.50', $csv);
    }

    public function test_safe_cell_leaves_numbers_and_plain_strings(): void
    {
        $svc = app(PayrollExportService::class);

        $this->assertSame(-5, $svc->safeCell(-5));
        $this->assertSame('Jane', $svc->safeCell('Jane'));
        $this->assertSame("'-1+1", $svc->safeCell('-1+1'));
        $this->assertSame("'+1", $svc->safeCell('+1'));
        $this->assertSame("'\tx", $svc->safeCell("\tx"));
        $this->assertSame("'\rx", $svc->safeCell("\rx"));
    }
}
