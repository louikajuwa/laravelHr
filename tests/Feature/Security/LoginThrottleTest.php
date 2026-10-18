<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sixth_attempt_is_blocked_with_seconds_message(): void
    {
        RateLimiter::clear('victim@example.com|127.0.0.1');
        User::factory()->create(['email' => 'victim@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'Victim@Example.com', 'password' => 'wrong'])
                ->assertSessionHasErrors('email');
        }

        $response = $this->post('/login', ['email' => 'victim@example.com', 'password' => 'password']);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('seconds', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_successful_login_clears_attempts(): void
    {
        RateLimiter::clear('ok@example.com|127.0.0.1');
        User::factory()->create(['email' => 'ok@example.com']);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', ['email' => 'ok@example.com', 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => 'ok@example.com', 'password' => 'password']);
        $this->assertAuthenticated();
        $this->assertSame(0, RateLimiter::attempts('ok@example.com|127.0.0.1'));
    }
}
