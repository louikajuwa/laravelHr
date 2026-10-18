<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = 'If an account exists for that email, a reset link has been sent.';

    public function test_pages_render(): void
    {
        $this->get(route('password.request'))->assertOk()->assertSee('Send reset link');
        $this->get(route('password.reset', 'tok'))->assertOk()->assertSee('Reset password');
        $this->get(route('login'))->assertSee('Forgot password?');
    }

    public function test_reset_link_sent_to_active_user(): void
    {
        Notification::fake();
        $user = User::factory()->create(['is_active' => true]);

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status', self::MESSAGE);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_and_inactive_get_same_message_without_notification(): void
    {
        Notification::fake();
        $inactive = User::factory()->create(['is_active' => false]);

        $this->post(route('password.email'), ['email' => 'nobody@example.test'])->assertSessionHas('status', self::MESSAGE);
        $this->post(route('password.email'), ['email' => $inactive->email])->assertSessionHas('status', self::MESSAGE);

        Notification::assertNothingSent();
    }

    public function test_reset_with_valid_token_changes_password(): void
    {
        $user = User::factory()->create(['is_active' => true, 'password' => 'old-password-1']);
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token, 'email' => $user->email,
            'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->post(route('login'), ['email' => $user->email, 'password' => 'old-password-1'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_invalid_token_fails(): void
    {
        $user = User::factory()->create(['is_active' => true, 'password' => 'old-password-1']);

        $this->post(route('password.update'), [
            'token' => 'bogus', 'email' => $user->email,
            'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password-1', $user->fresh()->password));
    }

    public function test_inactive_user_cannot_reset(): void
    {
        $user = User::factory()->create(['is_active' => false, 'password' => 'old-password-1']);
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token, 'email' => $user->email,
            'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password-1', $user->fresh()->password));
    }

    public function test_forgot_password_is_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.email'), ['email' => 'x@example.test'])->assertSessionHas('status');
        }

        $this->post(route('password.email'), ['email' => 'x@example.test'])->assertStatus(429);
    }
}
