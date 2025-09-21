<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UpdateAdminPermissions extends Command
{
    protected $signature = 'admin:update-permissions';
    protected $description = 'Actualiza todos los permisos del rol administrador';

    public function handle()
    {
        // Limpiar cache de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        $this->info('🔄 Actualizando permisos del administrador...');
        
        // Obtener el rol administrador
        $adminRole = Role::where('name', 'administrador')->first();
        
        if (!$adminRole) {
            $this->error('❌ Rol "administrador" no encontrado. Ejecuta primero: php artisan db:seed --class=UserSeeder');
            return 1;
        }
        
        $totalPermisos = Permission::count();
        $this->info("📋 Total de permisos en el sistema: {$totalPermisos}");
        
        // Asignar TODOS los permisos al administrador
        $adminRole->syncPermissions(Permission::all());
        
        $permisosAsignados = $adminRole->permissions()->count();
        
        $this->info("✅ Administrador actualizado con {$permisosAsignados} permisos");
        
        // Mostrar algunos permisos para verificación
        $this->info('🔍 Algunos permisos asignados:');
        $adminRole->permissions()->take(10)->each(function($permission) {
            $this->line("  - {$permission->name}");
        });
        
        if ($permisosAsignados > 10) {
            $this->line("  ... y " . ($permisosAsignados - 10) . " más");
        }
        
        $this->info('🎉 ¡Permisos del administrador actualizados exitosamente!');
        
        return 0;
    }
}