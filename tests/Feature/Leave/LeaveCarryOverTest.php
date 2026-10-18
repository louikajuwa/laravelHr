<?php

namespace Tests\Feature\Leave;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveCarryOverTest extends TestCase
{
    use RefreshDatabase;

    private function prev(User $u, LeaveType $t, array $attrs): void
    {
        LeaveBalance::create(array_merge([
            'user_id' => $u->id, 'leave_type_id' => $t->id, 'year' => 2025,
            'allocated' => 10, 'used' => 0, 'pending' => 0, 'carried_over' => 0,
        ], $attrs));
    }

    private function carried(User $u, LeaveType $t): float
    {
        return (float) LeaveBalance::where(['user_id' => $u->id, 'leave_type_id' => $t->id, 'year' => 2026])->value('carried_over');
    }

    public function test_carry_over_is_capped(): void
    {
        $u = $this->employee();
        $t = LeaveType::factory()->create(['carry_over' => true, 'max_carry_over_days' => 5]);
        $this->prev($u, $t, ['used' => 2]); // 8 available

        app(LeaveService::class)->allocateYearly(2026);

        $this->assertEquals(5, $this->carried($u, $t));
    }

    public function test_cap_zero_means_no_cap(): void
    {
        $u = $this->employee();
        $t = LeaveType::factory()->create(['carry_over' => true, 'max_carry_over_days' => 0]);
        $this->prev($u, $t, ['used' => 2, 'pending' => 1, 'carried_over' => 2]); // 10+2-2-1 = 9

        app(LeaveService::class)->allocateYearly(2026);

        $this->assertEquals(9, $this->carried($u, $t));
    }

    public function test_disabled_type_carries_nothing(): void
    {
        $u = $this->employee();
        $t = LeaveType::factory()->create(['carry_over' => false, 'max_carry_over_days' => 5]);
        $this->prev($u, $t, []);

        app(LeaveService::class)->allocateYearly(2026);

        $this->assertEquals(0, $this->carried($u, $t));
    }

    public function test_idempotent_rerun(): void
    {
        $u = $this->employee();
        $t = LeaveType::factory()->create(['carry_over' => true, 'max_carry_over_days' => 5]);
        $this->prev($u, $t, ['used' => 7]); // 3 available

        $svc = app(LeaveService::class);
        $svc->allocateYearly(2026);
        $svc->allocateYearly(2026);

        $this->assertEquals(3, $this->carried($u, $t));
        $this->assertSame(1, LeaveBalance::where(['user_id' => $u->id, 'leave_type_id' => $t->id, 'year' => 2026])->count());
    }

    public function test_negative_previous_available_carries_zero(): void
    {
        $u = $this->employee();
        $t = LeaveType::factory()->create(['carry_over' => true, 'max_carry_over_days' => 5]);
        $this->prev($u, $t, ['used' => 14]);

        app(LeaveService::class)->allocateYearly(2026);

        $this->assertEquals(0, $this->carried($u, $t));
    }

    public function test_balance_for_new_year_carries_too(): void
    {
        $u = $this->employee();
        $t = LeaveType::factory()->create(['carry_over' => true, 'max_carry_over_days' => 4]);
        $this->prev($u, $t, ['used' => 3]);

        $b = app(LeaveService::class)->balanceFor($u, $t, 2026);

        $this->assertEquals(4, $b->carried_over);
    }
}
