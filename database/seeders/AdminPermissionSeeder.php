<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Este seeder se ejecuta al FINAL de todos los demás seeders
     * para garantizar que el administrador tenga TODOS los permisos
     * que puedan haber sido creados por cualquier otro seeder.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        $this->command->info('🔄 Verificando permisos del administrador...');
        
        // Obtener todos los permisos que existen en la base de datos
        $allPermissions = Permission::all();
        $totalPermisos = $allPermissions->count();
        
        if ($totalPermisos === 0) {
            $this->command->warn('⚠️  No se encontraron permisos en la base de datos');
            return;
        }
        
        // Asegurar que el rol administrador existe
        $adminRole = Role::firstOrCreate(['name' => 'administrador']);
        
        // Asignar TODOS los permisos al rol administrador
        $adminRole->syncPermissions($allPermissions);
        
        // Buscar el usuario admin
        $adminUser = User::where('email', 'admin@example.com')->first();
        
        if ($adminUser) {
            // Asegurar que el usuario tenga el rol de administrador
            if (!$adminUser->hasRole('administrador')) {
                $adminUser->assignRole($adminRole);
                $this->command->info('✅ Rol administrador asignado al usuario admin@example.com');
            }
            
            // DOBLE GARANTÍA: Asignar TODOS los permisos directamente al usuario
            // Esto asegura que tenga permisos incluso si hay problemas con el rol
            $adminUser->syncPermissions($allPermissions);
            
            // Verificar permisos finales
            $userPermissions = $adminUser->getAllPermissions();
            $userPermissionsCount = $userPermissions->count();
            
            $this->command->info("✅ Usuario admin@example.com configurado:");
            $this->command->info("   - Rol: administrador");
            $this->command->info("   - Permisos totales en sistema: {$totalPermisos}");
            $this->command->info("   - Permisos asignados al usuario: {$userPermissionsCount}");
            
            if ($userPermissionsCount === $totalPermisos) {
                $this->command->info('🎉 ¡PERFECTO! El administrador tiene TODOS los permisos');
            } else {
                $this->command->error('❌ ERROR: El administrador NO tiene todos los permisos');
                $this->command->error("   Faltan: " . ($totalPermisos - $userPermissionsCount) . " permisos");
                
                // Mostrar permisos faltantes
                $assignedPermissions = $userPermissions->pluck('name')->toArray();
                $allPermissionNames = $allPermissions->pluck('name')->toArray();
                $missingPermissions = array_diff($allPermissionNames, $assignedPermissions);
                
                if (!empty($missingPermissions)) {
                    $this->command->error('   Permisos faltantes: ' . implode(', ', $missingPermissions));
                }
            }
            
            // Listar todos los permisos para verificación
            $this->command->info("\n📋 Lista completa de permisos asignados:");
            foreach ($userPermissions->sortBy('name') as $permission) {
                $this->command->line("   • {$permission->name}");
            }
            
        } else {
            $this->command->error('❌ No se encontró el usuario admin@example.com');
            $this->command->info('💡 Asegúrate de que el UserSeeder se haya ejecutado antes');
        }
        
        $this->command->info("\n🔒 Configuración de permisos de administrador completada");
    }
}