<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('u@example.com|127.0.0.1');
    }

    public function test_login_success(): void
    {
        $user = $this->employee(['email' => 'u@example.com']);

        $this->post('/login', ['email' => 'u@example.com', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_rejected(): void
    {
        $this->employee(['email' => 'u@example.com']);

        $this->post('/login', ['email' => 'u@example.com', 'password' => 'nope'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_rejected(): void
    {
        $this->employee(['email' => 'u@example.com', 'is_active' => false]);

        $this->post('/login', ['email' => 'u@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logout(): void
    {
        $user = $this->employee();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
