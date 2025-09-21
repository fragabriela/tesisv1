<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class VerifyAdminPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:verify-permissions {--fix : Automatically fix missing permissions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that the admin user has all permissions and optionally fix missing ones';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando permisos del administrador...');
        
        // Buscar el usuario admin
        $adminUser = User::where('email', 'admin@example.com')->first();
        
        if (!$adminUser) {
            $this->error('❌ No se encontró el usuario admin@example.com');
            $this->info('💡 Ejecuta: php artisan db:seed --class=UserSeeder');
            return Command::FAILURE;
        }
        
        // Obtener todos los permisos del sistema
        $allPermissions = Permission::all();
        $totalPermissions = $allPermissions->count();
        
        if ($totalPermissions === 0) {
            $this->warn('⚠️  No hay permisos en el sistema');
            return Command::SUCCESS;
        }
        
        // Obtener permisos del usuario admin
        $userPermissions = $adminUser->getAllPermissions();
        $userPermissionsCount = $userPermissions->count();
        
        // Información general
        $this->info("👤 Usuario: {$adminUser->name} ({$adminUser->email})");
        $this->info("🎭 Roles: " . $adminUser->roles->pluck('name')->implode(', '));
        $this->info("📊 Permisos totales en sistema: {$totalPermissions}");
        $this->info("📊 Permisos asignados al usuario: {$userPermissionsCount}");
        
        // Verificar si tiene todos los permisos
        if ($userPermissionsCount === $totalPermissions) {
            $this->info('🎉 ¡PERFECTO! El administrador tiene TODOS los permisos');
            
            if ($this->option('verbose')) {
                $this->info("\n📋 Lista de permisos:");
                foreach ($userPermissions->sortBy('name') as $permission) {
                    $this->line("   ✅ {$permission->name}");
                }
            }
            
            return Command::SUCCESS;
        }
        
        // Hay permisos faltantes
        $assignedPermissions = $userPermissions->pluck('name')->toArray();
        $allPermissionNames = $allPermissions->pluck('name')->toArray();
        $missingPermissions = array_diff($allPermissionNames, $assignedPermissions);
        $missingCount = count($missingPermissions);
        
        $this->error("❌ ERROR: Faltan {$missingCount} permisos");
        
        $this->warn("\n🚫 Permisos faltantes:");
        foreach ($missingPermissions as $permission) {
            $this->line("   • {$permission}");
        }
        
        // Opción para corregir automáticamente
        if ($this->option('fix')) {
            $this->info("\n🔧 Corrigiendo permisos automáticamente...");
            
            // Asegurar rol administrador
            $adminRole = Role::firstOrCreate(['name' => 'administrador']);
            $adminRole->syncPermissions($allPermissions);
            
            if (!$adminUser->hasRole('administrador')) {
                $adminUser->assignRole($adminRole);
            }
            
            // Asignar todos los permisos directamente
            $adminUser->syncPermissions($allPermissions);
            
            // Verificar nuevamente
            $updatedPermissions = $adminUser->getAllPermissions();
            $updatedCount = $updatedPermissions->count();
            
            if ($updatedCount === $totalPermissions) {
                $this->info('✅ ¡Permisos corregidos exitosamente!');
                return Command::SUCCESS;
            } else {
                $this->error('❌ No se pudieron corregir todos los permisos');
                return Command::FAILURE;
            }
        } else {
            $this->info("\n💡 Para corregir automáticamente, ejecuta:");
            $this->line("   php artisan admin:verify-permissions --fix");
            $this->info("\n💡 O ejecuta el seeder completo:");
            $this->line("   php artisan db:seed --class=AdminPermissionSeeder");
            
            return Command::FAILURE;
        }
    }
}
