<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSelfProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_delete_or_deactivate_self(): void
    {
        $admin = $this->admin();
        $this->admin();

        $this->actingAs($admin)->delete(route('hr.employees.destroy', $admin))->assertSessionHasErrors('employee');
        $this->actingAs($admin)->post(route('hr.employees.toggle', $admin))->assertSessionHasErrors('employee');

        $this->assertNotNull(User::find($admin->id));
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_cannot_remove_own_admin_role(): void
    {
        $admin = $this->admin();
        $this->admin();

        $this->actingAs($admin)->put(route('hr.employees.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'employee',
        ])->assertSessionHasErrors('role');

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_admin_can_delete_another_admin_when_others_remain(): void
    {
        $admin = $this->admin();
        $other = $this->admin();

        $this->actingAs($admin)->delete(route('hr.employees.destroy', $other))->assertSessionHasNoErrors();
        $this->assertNull(User::find($other->id));
    }

    public function test_admin_can_deactivate_an_employee(): void
    {
        $admin = $this->admin();
        $emp = $this->employee();

        $this->actingAs($admin)->post(route('hr.employees.toggle', $emp))->assertSessionHasNoErrors();
        $this->assertFalse($emp->fresh()->is_active);
    }
}
