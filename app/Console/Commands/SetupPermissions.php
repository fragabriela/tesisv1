<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SetupPermissions extends Command
{
    protected $signature = 'setup:permissions';
    protected $description = 'Setup permissions for the application';

    public function handle()
    {
        $this->info('🔧 Configurando permisos...');

        // Crear permisos
        $permissions = [
            'desplegar proyectos',
            'gestionar proyectos', 
            'ver proyectos'
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $this->info("✅ Permiso: {$permissionName}");
        }

        // Configurar usuario admin
        $admin = User::where('email', 'admin@example.com')->first();
        if ($admin) {
            $this->info("✅ Usuario admin encontrado: {$admin->email}");
            
            // Crear y asignar rol admin
            $adminRole = Role::firstOrCreate(['name' => 'admin']);
            if (!$admin->hasRole('admin')) {
                $admin->assignRole($adminRole);
                $this->info("✅ Rol admin asignado");
            }
            
            // Asignar permisos al rol
            foreach ($permissions as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();
                if ($permission && !$adminRole->hasPermissionTo($permission)) {
                    $adminRole->givePermissionTo($permission);
                    $this->info("✅ Permiso '{$permissionName}' asignado");
                }
            }
            
            // Mostrar permisos finales
            $this->info("\n🔑 Permisos del usuario admin:");
            foreach ($admin->getAllPermissions() as $permission) {
                $this->info("  - {$permission->name}");
            }
            
        } else {
            $this->error("❌ Usuario admin no encontrado");
        }

        $this->info("\n✅ Configuración de permisos completada!");
    }
}