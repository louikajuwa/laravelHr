<?php

namespace Tests\Feature\Leave;

use App\Models\LeaveType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    private function submit(array $over = [])
    {
        $type = LeaveType::factory()->create();

        return $this->actingAs($this->employee())->post(route('me.leave.store'), array_merge([
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'reason' => 'x',
        ], $over));
    }

    public function test_valid_request_is_accepted(): void
    {
        $this->submit()->assertSessionHasNoErrors()->assertRedirect(route('me.leave.index'));
        $this->assertDatabaseCount('leave_requests', 1);
    }

    public function test_range_over_a_year_is_rejected(): void
    {
        $this->submit(['end_date' => now()->addDays(2 + 366)->toDateString()])->assertSessionHasErrors('end_date');
        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_past_start_is_rejected(): void
    {
        $this->submit(['start_date' => now()->subDays(2)->toDateString(), 'end_date' => now()->toDateString()])
            ->assertSessionHasErrors('start_date');
    }

    public function test_inactive_type_is_rejected(): void
    {
        $type = LeaveType::factory()->create(['is_active' => false]);
        $this->submit(['leave_type_id' => $type->id])->assertSessionHasErrors('leave_type_id');
    }

    public function test_reason_too_long_is_rejected(): void
    {
        $this->submit(['reason' => str_repeat('a', 1001)])->assertSessionHasErrors('reason');
    }
}
