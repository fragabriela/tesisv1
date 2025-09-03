<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up()
    {
        // Crear permisos para el módulo de documentos
        $permisos = [
            'ver documentos',
            'crear documentos', 
            'editar documentos',
            'eliminar documentos',
            'exportar documentos'
        ];
        
        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }
        
        // Asignar permisos al rol administrador
        $adminRole = Role::where('name', 'administrador')->first();
        if ($adminRole) {
            foreach ($permisos as $permiso) {
                $adminRole->givePermissionTo($permiso);
            }
        }
    }

    public function down()
    {
        $permisos = [
            'ver documentos',
            'crear documentos', 
            'editar documentos',
            'eliminar documentos',
            'exportar documentos'
        ];
        
        foreach ($permisos as $permiso) {
            $permission = Permission::where('name', $permiso)->first();
            if ($permission) {
                $permission->delete();
            }
        }
    }
};