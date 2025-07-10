<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ProyectosPermissionSeederSimple extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos para gestión de proyectos
        $permissions = [
            'ver proyectos',
            'ver proyectos no visibles',
            'configurar proyectos',
            'desplegar proyectos',
            'gestionar proyectos',
            'monitorear proyectos'
        ];

        // Crear permisos que no existen
        $createdPermissions = [];
        foreach ($permissions as $permission) {
            // Verificar si el permiso ya existe antes de crearlo
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
                $createdPermissions[] = $permission;
                $this->command->info("Permiso '$permission' creado correctamente.");
            } else {
                $this->command->comment("El permiso '$permission' ya existe. Omitiendo.");
            }
        }

        $this->command->info('Permisos creados: ' . count($createdPermissions));
        $this->command->info('Permisos omitidos: ' . (count($permissions) - count($createdPermissions)));
    }
}
