<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Create the specific permission
        $permission = Permission::firstOrCreate([
            'name' => 'view admin panel',
        ]);
        
        $this->command->info('Permission "view admin panel" created or already exists.');

        // 3. Create the Admin Role and assign the permission
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->givePermissionTo($permission);

        $this->command->info('Role "Admin" created and assigned "view admin panel" permission.');

        // 4. Assign the Admin Role to the first user (User ID 1)
        $adminUser = User::find(1);

        if ($adminUser) {
            $adminUser->assignRole($adminRole);
            $this->command->info("Role 'Admin' assigned to user: {$adminUser->name}.");
        } else {
            $this->command->warn('User with ID 1 not found. Skipping role assignment.');
        }
    }
}