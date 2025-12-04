<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Products
            'view:products',
            'create:products',
            'update:products',
            'delete:products',
            'publish:products',
            
            // Orders
            'view:orders',
            'create:orders',
            'update:orders',
            'cancel:orders',
            'refund:orders',
            
            // Inventory
            'view:inventory',
            'adjust:inventory',
            'reserve:inventory',
            
            // Customers
            'view:customers',
            'create:customers',
            'update:customers',
            'delete:customers',
            
            // Employees
            'view:employees',
            'create:employees',
            'update:employees',
            'delete:employees',
            'manage:employees',
            
            // Reports
            'view:reports',
            'export:reports',
            
            // Settings
            'manage:settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles
        $owner = Role::create(['name' => 'owner']);
        $admin = Role::create(['name' => 'admin']);
        $manager = Role::create(['name' => 'manager']);
        $staff = Role::create(['name' => 'staff']);
        $readonly = Role::create(['name' => 'readonly']);

        // Assign permissions to roles
        $owner->givePermissionTo(Permission::all());
        
        $admin->givePermissionTo([
            'view:products', 'create:products', 'update:products', 'delete:products', 'publish:products',
            'view:orders', 'create:orders', 'update:orders', 'cancel:orders', 'refund:orders',
            'view:inventory', 'adjust:inventory', 'reserve:inventory',
            'view:customers', 'create:customers', 'update:customers', 'delete:customers',
            'view:employees', 'create:employees', 'update:employees',
            'view:reports', 'export:reports',
        ]);
        
        $manager->givePermissionTo([
            'view:products', 'create:products', 'update:products', 'publish:products',
            'view:orders', 'create:orders', 'update:orders', 'cancel:orders',
            'view:inventory', 'adjust:inventory',
            'view:customers', 'create:customers', 'update:customers',
            'view:reports',
        ]);
        
        $staff->givePermissionTo([
            'view:products',
            'view:orders', 'update:orders',
            'view:inventory',
            'view:customers',
        ]);
        
        $readonly->givePermissionTo([
            'view:products',
            'view:orders',
            'view:inventory',
            'view:customers',
            'view:reports',
        ]);
    }
}

