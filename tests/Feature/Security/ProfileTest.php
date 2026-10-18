<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        return array_merge(['name' => 'Jane', 'email' => 'jane@example.com'], $extra);
    }

    public function test_email_change_requires_current_password(): void
    {
        $user = $this->employee(['email' => 'old@example.com']);

        $this->actingAs($user)->put(route('me.profile.update'), $this->payload())
            ->assertSessionHasErrors('current_password');
        $this->assertSame('old@example.com', $user->fresh()->email);

        $this->actingAs($user)->put(route('me.profile.update'), $this->payload(['current_password' => 'wrong']))
            ->assertSessionHasErrors('current_password');

        $this->actingAs($user)->put(route('me.profile.update'), $this->payload(['current_password' => 'password']))
            ->assertSessionHasNoErrors();
        $this->assertSame('jane@example.com', $user->fresh()->email);
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = $this->employee(['email' => 'jane@example.com']);
        $new = ['password' => 'newpassword1', 'password_confirmation' => 'newpassword1'];

        $this->actingAs($user)->put(route('me.profile.update'), $this->payload($new))
            ->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));

        $this->actingAs($user)->put(route('me.profile.update'), $this->payload($new + ['current_password' => 'password']))
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('newpassword1', $user->fresh()->password));
    }

    public function test_non_sensitive_update_needs_no_current_password(): void
    {
        $user = $this->employee(['email' => 'jane@example.com']);

        $this->actingAs($user)->put(route('me.profile.update'), $this->payload(['name' => 'New Name']))
            ->assertSessionHasNoErrors();
        $this->assertSame('New Name', $user->fresh()->name);
    }
}
