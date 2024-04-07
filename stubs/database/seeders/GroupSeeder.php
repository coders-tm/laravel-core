<?php

namespace Database\Seeders;

use Coderstm\Models\Group;
use Coderstm\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

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

        $group->syncPermissions(collect(Permission::all())->mapWithKeys(function ($permission, $key) {
            return [$key => [
                'id' => $permission['id'],
                'access' => true,
            ]];
        }));

        $sales->syncPermissions(collect(Permission::where('scope', 'like', '%list%')->orWhere('scope', 'like', '%view%')->get())
            ->mapWithKeys(function ($permission, $key) {
                return [$key => [
                    'id' => $permission['id'],
                    'access' => true,
                ]];
            }));
    }
}
