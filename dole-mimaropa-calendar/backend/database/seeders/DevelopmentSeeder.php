<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\OfficeMembership;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DEVELOPMENT ONLY. Refuses to run in production. Password comes from DEV_SEED_PASSWORD
 * (see docs/SETUP.md); nothing is committed.
 */
class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('DevelopmentSeeder refuses to run in production.');

            return;
        }
        $password = env('DEV_SEED_PASSWORD') ?: throw new \RuntimeException('Set DEV_SEED_PASSWORD in your local .env first.');
        $org = \App\Models\Organization::firstOrCreate(['name' => 'DOLE MIMAROPA (dev)']);

        $regional = Office::firstOrCreate(['code' => 'RO'], ['organization_id' => $org->id, 'name' => 'Regional Office', 'level' => 'regional']);
        $a = Office::firstOrCreate(['code' => 'FO-A'], ['organization_id' => $org->id, 'name' => 'Field Office A', 'level' => 'field', 'parent_office_id' => $regional->id]);
        $b = Office::firstOrCreate(['code' => 'FO-B'], ['organization_id' => $org->id, 'name' => 'Field Office B', 'level' => 'field', 'parent_office_id' => $regional->id]);

        $make = function (string $email, string $name, Office $office, string $roleSlug) use ($password) {
            $u = User::firstOrCreate(['email' => $email], [
                'name' => $name, 'password' => Hash::make($password), 'primary_office_id' => $office->id, 'is_active' => true,
            ]);
            OfficeMembership::firstOrCreate(['user_id' => $u->id, 'office_id' => $office->id],
                ['role_id' => Role::where('slug', $roleSlug)->value('id'), 'is_primary' => true]);
        };
        $make('regional.admin@dev.invalid', 'Dev Regional Admin', $regional, 'regional-admin');
        $make('admin.a@dev.invalid', 'Dev Office A Admin', $a, 'office-admin');
        $make('staff.a@dev.invalid', 'Dev Staff A', $a, 'personnel');
        $make('admin.b@dev.invalid', 'Dev Office B Admin', $b, 'office-admin');
        $make('staff.b@dev.invalid', 'Dev Staff B', $b, 'personnel');
    }
}
