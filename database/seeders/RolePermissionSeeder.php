<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the baseline role/permission graph described in docs/SRS.md §2.2.
 *
 * Permissions are deliberately representative rather than exhaustive — FR-AUTHZ-002
 * requires permissions to be manageable from an admin UI without a code deploy, so
 * this seeder establishes a sane starting point, not the final permission set.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private const array ROLE_PERMISSIONS = [
        'Super Administrator' => ['*'],
        'Hotel Owner' => [
            'reports.view', 'accounting.view', 'branches.manage', 'users.view',
        ],
        'General Manager' => [
            'reservations.manage', 'rooms.manage', 'guests.manage', 'housekeeping.manage',
            'maintenance.manage', 'restaurant.manage', 'inventory.manage', 'procurement.manage', 'accounting.view',
            'hr.manage', 'reports.view', 'users.manage', 'folios.manage', 'folios.void',
        ],
        'Branch Manager' => [
            'reservations.manage', 'rooms.manage', 'guests.manage', 'housekeeping.manage',
            'maintenance.manage', 'restaurant.manage', 'inventory.manage', 'procurement.manage', 'reports.view',
            'folios.manage', 'folios.void',
        ],
        'Receptionist' => [
            'reservations.view', 'reservations.create', 'reservations.update',
            'front_desk.check_in', 'front_desk.check_out', 'guests.manage', 'folios.manage',
        ],
        'Reservation Officer' => [
            'reservations.manage', 'guests.view', 'guests.create',
        ],
        'Housekeeping Supervisor' => [
            'housekeeping.manage', 'rooms.view', 'maintenance.create',
        ],
        'Housekeeping Staff' => [
            'housekeeping.view', 'housekeeping.update',
        ],
        'Restaurant Manager' => [
            'restaurant.manage', 'inventory.manage', 'reports.view',
        ],
        'Waiter' => [
            'restaurant.orders.create', 'restaurant.orders.view',
        ],
        'Chef' => [
            'restaurant.orders.view', 'restaurant.orders.update_kitchen_status',
        ],
        'Accountant' => [
            'accounting.manage', 'reports.view', 'folios.view', 'payments.view', 'folios.void',
        ],
        'Cashier' => [
            'folios.manage', 'payments.process', 'payments.refund',
        ],
        'Maintenance Officer' => [
            'maintenance.manage', 'rooms.view',
        ],
        'Security Officer' => [
            'guests.view', 'audit.view',
        ],
        'HR' => [
            'hr.manage',
        ],
        'Auditor' => [
            'reports.view', 'audit.view', 'accounting.view',
        ],
        'Guest' => [
            'own_profile.manage', 'own_reservations.manage', 'own_invoices.view',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = collect(self::ROLE_PERMISSIONS)
            ->flatten()
            ->reject(fn (string $permission): bool => $permission === '*')
            ->unique()
            ->values();

        $permissionNames->each(fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if (in_array('*', $permissions, true)) {
                $role->syncPermissions(Permission::where('guard_name', 'web')->get());

                continue;
            }

            $role->syncPermissions($permissions);
        }
    }
}
