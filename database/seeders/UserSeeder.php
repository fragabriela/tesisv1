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
            
            // Carreras
            'ver carreras',
            'crear carreras',
            'editar carreras',
            'eliminar carreras',
            'exportar carreras',
            
            // Tutores
            'ver tutores',
            'crear tutores',
            'editar tutores',
            'eliminar tutores',
            'exportar tutores',
            
            // Tesis
            'ver tesis',
            'crear tesis',
            'editar tesis',
            'eliminar tesis',
            'exportar tesis',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        // Admin
        $adminRole = Role::create(['name' => 'administrador']);
        $adminRole->givePermissionTo(Permission::all());

        // Coordinador
        $coordinadorRole = Role::create(['name' => 'coordinador']);
        $coordinadorRole->givePermissionTo([
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
        ]);

        // Tutor
        $tutorRole = Role::create(['name' => 'tutor']);
        $tutorRole->givePermissionTo([
            'ver dashboard',
            'ver alumnos',
            'ver tesis',
            'editar tesis',
        ]);

        // Create admin user
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole($adminRole);

        // Create coordinador user
        $coordinador = User::create([
            'name' => 'Coordinador',
            'email' => 'coordinador@example.com',
            'password' => Hash::make('password'),
        ]);
        $coordinador->assignRole($coordinadorRole);

        // Create tutor user
        $tutor = User::create([
            'name' => 'Tutor',
            'email' => 'tutor@example.com',
            'password' => Hash::make('password'),
        ]);
        $tutor->assignRole($tutorRole);
    }
}
