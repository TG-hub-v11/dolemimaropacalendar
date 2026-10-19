<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/** Roles and permissions only (safe for every environment). No accounts are created here. */
class AccessSeeder extends Seeder
{
    public const MATRIX = [
        'system-admin' => ['System Administrator', ['scope.regional', 'user.manage', 'role.manage', 'office.manage', 'audit.view', 'event.manage', 'task.manage', 'announcement.publish', 'report.view']],
        'regional-admin' => ['Regional Administrator', ['scope.regional', 'event.manage', 'task.manage', 'announcement.publish', 'report.view']],
        'office-admin' => ['Office Administrator', ['event.manage', 'task.manage', 'announcement.publish', 'report.view']],
        'personnel' => ['Personnel', []],
        'viewer' => ['Read-Only Viewer', []],
    ];

    public function run(): void
    {
        foreach (self::MATRIX as $slug => [$name, $perms]) {
            $role = Role::updateOrCreate(['slug' => $slug], ['name' => $name]);
            $ids = collect($perms)->map(fn ($p) => Permission::firstOrCreate(['slug' => $p])->id);
            $role->permissions()->sync($ids);
        }
    }
}
