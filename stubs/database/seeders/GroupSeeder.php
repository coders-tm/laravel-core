<?php

namespace Database\Seeders;

use Coderstm\Models\Group;
use Coderstm\Models\Permission;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $group = Group::firstOrCreate([
            'name' => 'Admin',
        ], [
            'description' => 'Full access to the system',
        ]);

        $sales = Group::firstOrCreate([
            'name' => 'Sales',
        ], [
            'description' => 'Limited access to the system',
        ]);

        $group->syncPermissions(Permission::all()->map(function ($permission) {
            return [
                'scope' => $permission->scope,
                'access' => true,
            ];
        }));

        $salesPermissions = Permission::all()->filter(function ($permission) {
            return str_contains($permission->scope, 'list') || str_contains($permission->scope, 'view');
        });

        $sales->syncPermissions($salesPermissions->map(function ($permission) {
            return [
                'scope' => $permission->scope,
                'access' => true,
            ];
        }));
    }
}
