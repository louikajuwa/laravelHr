<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchEscapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_like_wildcards_are_literal(): void
    {
        User::factory()->create(['name' => 'Alice Smith']);
        User::factory()->create(['name' => '100% Real']);

        $this->assertSame(['100% Real'], User::search('%')->pluck('name')->all());
        $this->assertSame([], User::search('_')->pluck('name')->all());
        $this->assertSame(['Alice Smith'], User::search('Alice')->pluck('name')->all());
    }
}
