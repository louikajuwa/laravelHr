<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DepartmentShiftSeeder::class,
            LeaveTypeSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
