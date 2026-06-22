<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

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

        $this->seedAdminUser();

        // Demo advisor only exists in non-production environments so prod stays clean.
        if (app()->environment(['local', 'testing', 'staging'])) {
            $this->seedDemoAdvisor();
        }

        $this->command->info('Roles and permissions seeded successfully.');
    }

    /**
     * Seed the bootstrap admin user. Credentials come from environment variables in production;
     * local/testing environments fall back to deterministic defaults so the dev workflow stays unchanged.
     */
    private function seedAdminUser(): void
    {
        $email = (string) env('SEED_ADMIN_EMAIL', 'admin@rentenblick.de');
        $password = env('SEED_ADMIN_PASSWORD');

        if (app()->environment('production') && empty($password)) {
            throw new \RuntimeException(
                'SEED_ADMIN_PASSWORD must be set in production before running db:seed.',
            );
        }

        $password ??= 'admin123!';

        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'email' => $email,
                'password' => bcrypt($password),
                'company' => 'RENTENBLICK.de',
                'plan' => User::PLAN_ENTERPRISE,
                'status' => User::STATUS_ACTIVE,
                'accept_terms' => true,
                'accept_privacy' => true,
                'email_verified_at' => now(),
            ],
        )->syncRoles([User::ROLE_ADMIN]);
    }

    /**
     * Seed the demo advisor used in local + automated tests. Never runs in production.
     */
    private function seedDemoAdvisor(): void
    {
        User::firstOrCreate(
            ['email' => 'berater@rentenblick.de'],
            [
                'name' => 'Max Mustermann',
                'first_name' => 'Max',
                'last_name' => 'Mustermann',
                'email' => 'berater@rentenblick.de',
                'password' => bcrypt('berater123!'),
                'company' => 'Musterberatung GmbH',
                'plan' => User::PLAN_PROFESSIONAL,
                'phone' => '+49 123 456789',
                'status' => User::STATUS_ACTIVE,
                'accept_terms' => true,
                'accept_privacy' => true,
                'email_verified_at' => now(),
            ],
        )->syncRoles([User::ROLE_ADVISOR]);
    }
}
