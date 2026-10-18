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

class PayrollOverlapTest extends TestCase
{
    use RefreshDatabase;

    public function test_overlapping_period_is_rejected(): void
    {
        $u = User::factory()->create();
        SalaryStructure::factory()->create(['user_id' => $u->id]);
        Payroll::factory()->create(['user_id' => $u->id, 'period_start' => '2026-01-15', 'period_end' => '2026-02-14']);

        $this->expectException(RuntimeException::class);
        app(PayrollService::class)->generate($u, Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'), $u);
    }

    public function test_batch_skips_overlapping_and_generates_others(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        SalaryStructure::factory()->create(['user_id' => $a->id]);
        SalaryStructure::factory()->create(['user_id' => $b->id]);
        Payroll::factory()->create(['user_id' => $a->id, 'period_start' => '2026-01-15', 'period_end' => '2026-02-14']);

        $r = app(PayrollService::class)->generateBatch(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'), $a);

        $this->assertSame(1, $r['generated']);
        $this->assertSame(1, $r['skipped']);
        $this->assertSame([], $r['failed']);
    }
}
