<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
          $permissions = [
            'view projects','create projects','edit projects', 'delete projects',
            'view users','create users','edit users','delete users',
            'view roles','create roles','edit roles','delete roles',
            'view tasks','create tasks','edit tasks','delete tasks',
            'view comments','create comments','edit comments','delete comments',
            'view chats','create chats','edit chats','delete chats',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $developer  = Role::firstOrCreate(['name' => 'Developer']);
        $client     = Role::firstOrCreate(['name' => 'Client']);

        $superAdmin->syncPermissions(Permission::all());
        $developer->syncPermissions(['view projects', 'view tasks']);
        $client->syncPermissions(['view projects']);

       

        $this->command->info('✅ Seeder completed successfully.');
    }
}

