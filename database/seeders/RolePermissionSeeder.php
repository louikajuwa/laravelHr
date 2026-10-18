<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'employees.manage', 'departments.manage', 'shifts.manage',
            'attendance.view_all', 'attendance.correct',
            'leave.approve', 'leave.manage_types',
            'payroll.generate', 'payroll.view_all',
            'reports.view', 'audit.view', 'dashboard.hr',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions($permissions);

        Role::findOrCreate('employee', 'web');
    }
}
