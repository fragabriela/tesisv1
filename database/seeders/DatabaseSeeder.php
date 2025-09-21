<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CarreraSeeder::class,
            TutorSeeder::class,
            AlumnoSeeder::class,
            TesisSeeder::class,
            ProyectosPermissionSeeder::class,
            FixEstadoValuesSeeder::class,
            // IMPORTANTE: AdminPermissionSeeder DEBE ejecutarse AL FINAL
            // para garantizar que el admin tenga TODOS los permisos
            // creados por cualquier seeder anterior
            AdminPermissionSeeder::class,
        ]);
    }
}
