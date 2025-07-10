<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProyectosPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */    public function run(): void
    {
        // Crear permisos para gestión de proyectos
        $permissions = [
            'ver proyectos',
            'ver proyectos no visibles',
            'configurar proyectos',
            'desplegar proyectos',
            'gestionar proyectos',
            'monitorear proyectos',
            'crear proyectos'
        ];

        foreach ($permissions as $permission) {
            // Verificar si el permiso ya existe antes de crearlo
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
                $this->command->info("Permiso '$permission' creado correctamente.");
            } else {
                $this->command->comment("El permiso '$permission' ya existe. Omitiendo.");
            }
        }        // Recopilar los permisos que existen en la base de datos
        $existingPermissions = Permission::whereIn('name', $permissions)->pluck('name')->toArray();
        
        // Verificar qué roles existen en el sistema
        $existingRoles = Role::pluck('name')->toArray();
        $this->command->info("Roles existentes: " . implode(', ', $existingRoles));
        
        // Crear roles básicos si no existen
        $roles = ['administrador', 'profesor', 'estudiante'];
        foreach ($roles as $roleName) {
            if (!in_array($roleName, $existingRoles)) {
                Role::create(['name' => $roleName]);
                $this->command->info("Rol '$roleName' creado.");
            }
        }
        
        // Asignar permisos a roles
        if (in_array('administrador', $existingRoles) || Role::where('name', 'administrador')->exists()) {
            $adminRole = Role::findByName('administrador');
            $adminRole->syncPermissions($existingPermissions);
            $this->command->info("Permisos asignados al rol 'administrador'");
        }
        
        if (in_array('profesor', $existingRoles) || Role::where('name', 'profesor')->exists()) {
            $profesorRole = Role::findByName('profesor');
            $profesorPermisos = array_intersect($existingPermissions, ['ver proyectos', 'configurar proyectos']);
            $profesorRole->syncPermissions($profesorPermisos);
            $this->command->info("Permisos asignados al rol 'profesor'");
        }
        
        if (in_array('estudiante', $existingRoles) || Role::where('name', 'estudiante')->exists()) {
            $estudianteRole = Role::findByName('estudiante');
            $estudiantePermisos = array_intersect($existingPermissions, ['ver proyectos']);
            $estudianteRole->syncPermissions($estudiantePermisos);
            $this->command->info("Permisos asignados al rol 'estudiante'");
        }// También podemos crear un nuevo rol específico para gestionar proyectos
        if (!Role::where('name', 'gestor_proyectos')->exists()) {
            $proyectosManagerRole = Role::create(['name' => 'gestor_proyectos']);
            $proyectosManagerRole->syncPermissions($existingPermissions);
            $this->command->info("Rol 'gestor_proyectos' creado y permisos asignados");
        } else {
            $proyectosManagerRole = Role::findByName('gestor_proyectos');
            $proyectosManagerRole->syncPermissions($existingPermissions);
            $this->command->info("Permisos actualizados para el rol 'gestor_proyectos'");
        }

        $this->command->info('Configuración de permisos de proyectos completada correctamente.');
    }
}
