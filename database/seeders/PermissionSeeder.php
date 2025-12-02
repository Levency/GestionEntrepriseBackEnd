<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Réinitialiser le cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $modules = [
            'sales' => ['create', 'update', 'delete', 'view'],
            'products' => ['create', 'update', 'delete', 'view'],
            'stock' => ['create', 'update', 'delete', 'view', 'adjust'],
            'movements' => ['create', 'update', 'delete', 'view'],
            'categories' => ['create', 'update', 'delete', 'view'],
            'employees' => ['create', 'update', 'delete', 'view'],
            'departments' => ['create', 'update', 'delete', 'view'],
            'attendance' => ['create', 'update', 'delete', 'view'],
            'payroll' => ['create', 'update', 'delete', 'view', 'process'],
            'taxes' => ['create', 'update', 'delete', 'view'],
            'reports' => ['sales', 'stock', 'financial', 'personnel'],
            'settings' => ['company', 'users', 'roles', 'system'],
        ];

        $allPermissions = [];
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permissionName = "{$module}.{$action}";
                Permission::create(['name' => $permissionName, 'guard_name' => 'sanctum']);
                $allPermissions[] = $permissionName;
            }
        }

        // Créer les rôles
        $admin = Role::firstOrCreate(['name' => 'Administrateur'], ['guard_name' => 'sanctum']);
        $admin->givePermissionTo($allPermissions);

        $manager = Role::firstOrCreate(['name' => 'Manager'], ['guard_name' => 'sanctum']);
        $manager->givePermissionTo([
            'sales.create', 'sales.update', 'sales.view',
            'products.create', 'products.update', 'products.view',
            'stock.view', 'stock.adjust',
            // ... etc
        ]);
    }
}