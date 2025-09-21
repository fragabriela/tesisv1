<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Dashboard
            'ver dashboard',
            
            // Alumnos
            'ver alumnos',
            'crear alumnos',
            'editar alumnos',
            'eliminar alumnos',
            'exportar alumnos',
            'importar alumnos',
            
            // Carreras
            'ver carreras',
            'crear carreras',
            'editar carreras',
            'eliminar carreras',
            'exportar carreras',
            'importar carreras',
            
            // Tutores
            'ver tutores',
            'crear tutores',
            'editar tutores',
            'eliminar tutores',
            'exportar tutores',
            'importar tutores',
            
            // Tesis
            'ver tesis',
            'crear tesis',
            'editar tesis',
            'eliminar tesis',
            'exportar tesis',
            
            // Documentos
            'ver documentos',
            'crear documentos',
            'editar documentos',
            'eliminar documentos',
            'exportar documentos',
            
            // Proyectos
            'ver proyectos',
            'crear proyectos',
            'editar proyectos',
            'eliminar proyectos',
            'monitorear proyectos',
            'configurar proyectos',
            'desplegar proyectos',
            'exportar proyectos',
            'ver proyectos no visibles',
            'gestionar proyectos',
            
            // Administración
            'administrar usuarios',
            'gestionar roles',
            'gestionar permisos',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        // ADMIN - SIEMPRE tiene TODOS los permisos (incluso nuevos que se agreguen)
        // Esto asegura que el administrador tenga acceso completo sin importar qué permisos se agreguen
        $adminRole = Role::firstOrCreate(['name' => 'administrador']);
        
        // Primero creamos todos los permisos base
        $allPermissions = Permission::all();
        $adminRole->syncPermissions($allPermissions);
        
        $this->command->info('✅ Administrador configurado con TODOS los permisos (' . $allPermissions->count() . ' permisos)');

        // Coordinador
        $coordinadorRole = Role::firstOrCreate(['name' => 'coordinador']);
        if ($coordinadorRole->permissions()->count() == 0) {
            $coordinadorRole->syncPermissions([
                'ver dashboard',
                'ver alumnos',
                'crear alumnos',
                'editar alumnos',
                'exportar alumnos',
                'ver carreras',
                'exportar carreras',
                'ver tutores',
                'crear tutores',
                'editar tutores',
                'exportar tutores',
                'ver tesis',
                'crear tesis',
                'editar tesis',
                'exportar tesis',
                'ver documentos',
                'crear documentos',
                'editar documentos',
                'exportar documentos',
                'ver proyectos',
                'crear proyectos',
                'monitorear proyectos',
                'exportar proyectos',
            ]);
        }

        // Tutor
        $tutorRole = Role::firstOrCreate(['name' => 'tutor']);
        if ($tutorRole->permissions()->count() == 0) {
            $tutorRole->syncPermissions([
                'ver dashboard',
                'ver tesis',
                'editar tesis',
                'ver documentos',
                'editar documentos',
                'ver proyectos',
            ]);
        }

        // Alumno
        $alumnoRole = Role::firstOrCreate(['name' => 'alumno']);
        if ($alumnoRole->permissions()->count() == 0) {
            $alumnoRole->syncPermissions([
                'ver tesis',
                'editar tesis',
                'ver documentos',
                'editar documentos',
                'ver proyectos',
                'crear proyectos',
                'editar proyectos',
            ]);
        }

        // Estudiante
        $estudianteRole = Role::firstOrCreate(['name' => 'estudiante']);
        if ($estudianteRole->permissions()->count() == 0) {
            $estudianteRole->syncPermissions([
                'ver tesis',
                'crear tesis',
                'editar tesis',
                'ver documentos',
                'crear documentos',
                'editar documentos',
                'ver proyectos',
                'crear proyectos',
                'editar proyectos',
            ]);
        }

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
            ]
        );
        
        // Asegurar que el admin tenga el rol de administrador
        if (!$admin->hasRole('administrador')) {
            $admin->assignRole($adminRole);
        }
        
        // FORZAR que el usuario admin tenga TODOS los permisos directamente
        // Esto es una doble garantía: permisos por rol + permisos directos
        $admin->syncPermissions(Permission::all());
        
        $this->command->info('✅ Usuario admin@example.com configurado con rol administrador y TODOS los permisos (' . $admin->getAllPermissions()->count() . ' permisos)');

        // Create coordinador user
        $coordinador = User::firstOrCreate(
            ['email' => 'coordinador@example.com'],
            [
                'name' => 'Coordinador',
                'password' => Hash::make('password'),
            ]
        );
        $coordinador->assignRole($coordinadorRole);

        // Create tutor user
        $tutor = User::firstOrCreate(
            ['email' => 'tutor@example.com'],
            [
                'name' => 'Tutor',
                'password' => Hash::make('password'),
            ]
        );
        $tutor->assignRole($tutorRole);

        // Create alumno user
        $alumno = User::firstOrCreate(
            ['email' => 'alumno@example.com'],
            [
                'name' => 'Alumno Ejemplo',
                'password' => Hash::make('password'),
            ]
        );
        $alumno->assignRole($alumnoRole);
    }
}
