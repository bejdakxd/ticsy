<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::create(['name' => 'resolver']);

        Permission::create([
            'name' => 'resolve_ticket'
        ]);
        Permission::create([
            'name' => 'set_group'
        ]);
        Permission::create([
            'name' => 'set_resolver'
        ]);
        Permission::create([
            'name' => 'set_priority'
        ]);
        Permission::create([
            'name' => 'set_status'
        ]);
        Permission::create([
            'name' => 'add_comments_to_all_tickets'
        ]);
        Permission::create([
            'name' => 'view_all_tickets'
        ]);

        $role->givePermissionTo('resolve_ticket', 'set_group', 'set_resolver', 'set_priority', 'set_status', 'add_comments_to_all_tickets', 'view_all_tickets');
    }
}