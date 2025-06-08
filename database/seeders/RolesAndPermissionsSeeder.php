<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions - use firstOrCreate to avoid duplicates
        $permissions = [
            // Client permissions
            'view clients',
            'create clients',
            'edit clients',
            'delete clients',
            
            // Rentencheck permissions
            'view rentenchecks',
            'create rentenchecks',
            'edit rentenchecks',
            'delete rentenchecks',
            'complete rentenchecks',
            'download pdf',
            
            // User management permissions (admin only)
            'view users',
            'create users',
            'edit users',
            'delete users',
            'block users',
            'unblock users',
            'assign roles',
            
            // Admin dashboard permissions
            'view admin dashboard',
            'view advisor statistics',
            'manage system settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions - use firstOrCreate to avoid duplicates
        
        // Financial Advisor Role
        $advisorRole = Role::firstOrCreate(['name' => User::ROLE_ADVISOR, 'guard_name' => 'web']);
        
        // Sync permissions for advisor role (this will update if permissions change)
        $advisorRole->syncPermissions([
            'view clients',
            'create clients',
            'edit clients',
            'delete clients',
            'view rentenchecks',
            'create rentenchecks',
            'edit rentenchecks',
            'delete rentenchecks',
            'complete rentenchecks',
            'download pdf',
        ]);

        // Admin Role - has all permissions
        $adminRole = Role::firstOrCreate(['name' => User::ROLE_ADMIN, 'guard_name' => 'web']);
        
        // Sync all permissions for admin role
        $adminRole->syncPermissions(Permission::all());

        // Create admin user if it doesn't exist
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@rentenblick.de'],
            [
                'name' => 'Admin',
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'email' => 'admin@rentenblick.de',
                'password' => bcrypt('admin123!'), // Change in production
                'company' => 'RENTENBLICK.de',
                'plan' => 'enterprise',
                'status' => User::STATUS_ACTIVE,
                'accept_terms' => true,
                'accept_privacy' => true,
                'email_verified_at' => now(),
            ]
        );

        // Ensure admin has the correct role
        if (!$adminUser->hasRole(User::ROLE_ADMIN)) {
            $adminUser->assignRole(User::ROLE_ADMIN);
        }

        // Create sample financial advisor if it doesn't exist
        $advisorUser = User::firstOrCreate(
            ['email' => 'berater@rentenblick.de'],
            [
                'name' => 'Max Mustermann',
                'first_name' => 'Max',
                'last_name' => 'Mustermann',
                'email' => 'berater@rentenblick.de',
                'password' => bcrypt('berater123!'), // Change in production
                'company' => 'Musterberatung GmbH',
                'plan' => 'professional',
                'phone' => '+49 123 456789',
                'status' => User::STATUS_ACTIVE,
                'accept_terms' => true,
                'accept_privacy' => true,
                'email_verified_at' => now(),
            ]
        );

        // Ensure advisor has the correct role
        if (!$advisorUser->hasRole(User::ROLE_ADVISOR)) {
            $advisorUser->assignRole(User::ROLE_ADVISOR);
        }

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Admin user: admin@rentenblick.de (password: admin123!)');
        $this->command->info('Advisor user: berater@rentenblick.de (password: berater123!)');
    }
} 