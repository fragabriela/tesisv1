<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UpdateRolePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'role:update-permissions {role} {--add=*} {--remove=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update permissions for a specific role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $roleName = $this->argument('role');
        $addPermissions = $this->option('add');
        $removePermissions = $this->option('remove');

        try {
            $role = Role::where('name', $roleName)->first();
            
            if (!$role) {
                $this->error("Rol '{$roleName}' no encontrado.");
                return;
            }

            $this->info("Actualizando permisos para el rol: {$role->name}");

            // Agregar permisos
            if (!empty($addPermissions)) {
                foreach ($addPermissions as $permission) {
                    $permissionObj = Permission::where('name', $permission)->first();
                    if ($permissionObj) {
                        $role->givePermissionTo($permission);
                        $this->info("✓ Agregado permiso: {$permission}");
                    } else {
                        $this->warn("⚠ Permiso no encontrado: {$permission}");
                    }
                }
            }

            // Remover permisos
            if (!empty($removePermissions)) {
                foreach ($removePermissions as $permission) {
                    $role->revokePermissionTo($permission);
                    $this->info("✓ Removido permiso: {$permission}");
                }
            }

            // Limpiar caché de permisos
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            $this->info("\n🎉 Permisos actualizados exitosamente!");
            
            // Mostrar permisos actuales
            $currentPermissions = $role->permissions->pluck('name')->toArray();
            $this->info("Permisos actuales del rol '{$role->name}':");
            foreach ($currentPermissions as $permission) {
                $this->line("  - {$permission}");
            }

        } catch (\Exception $e) {
            $this->error("Error al actualizar permisos: " . $e->getMessage());
        }
    }
}
