<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // Never let RefreshDatabase touch a non-test database.
        $db = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? '');
        if (! str_contains((string) $db, 'testing')) {
            throw new RuntimeException("Refusing to run tests against database [{$db}].");
        }

        parent::setUp();

        $this->withoutVite();
    }

    protected function seedRoles(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    protected function admin(array $attributes = []): User
    {
        $this->seedRoles();

        return tap(User::factory()->create($attributes))->assignRole('admin');
    }

    protected function employee(array $attributes = []): User
    {
        $this->seedRoles();

        return tap(User::factory()->create($attributes))->assignRole('employee');
    }
}
