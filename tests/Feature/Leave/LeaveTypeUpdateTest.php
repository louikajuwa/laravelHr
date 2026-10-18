<?php

namespace Tests\Feature\Leave;

use App\Models\LeaveType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveTypeUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function payload(LeaveType $t, array $over = []): array
    {
        return array_merge([
            'name' => $t->name, 'code' => $t->code, 'default_days_per_year' => 12, 'is_paid' => 1, 'is_active' => 1,
        ], $over);
    }

    public function test_update_keeping_same_code_passes(): void
    {
        $admin = $this->admin();
        $type = LeaveType::factory()->create(['code' => 'ANN']);

        $this->actingAs($admin)->put(route('hr.leave-types.update', $type), $this->payload($type, ['name' => 'Annual']))
            ->assertSessionHasNoErrors()->assertRedirect(route('hr.leave-types.index'));

        $this->assertSame('Annual', $type->fresh()->name);
    }

    public function test_update_to_another_type_code_fails(): void
    {
        $admin = $this->admin();
        LeaveType::factory()->create(['code' => 'SICK']);
        $type = LeaveType::factory()->create(['code' => 'ANN']);

        $this->actingAs($admin)->put(route('hr.leave-types.update', $type), $this->payload($type, ['code' => 'SICK']))
            ->assertSessionHasErrors('code');
    }
}
