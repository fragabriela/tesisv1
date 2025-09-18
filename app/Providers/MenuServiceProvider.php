<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class MenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            if (auth()->check()) {
                $user = auth()->user();
                $menuItems = $this->getMenuByRole($user);
                $view->with('menuItems', $menuItems);
            }
        });
    }

    /**
     * Get menu items based on user role
     */
    private function getMenuByRole($user)
    {
        $menu = [];

        // Dashboard - todos los usuarios autenticados
        $menu[] = [
            'title' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'route' => 'dashboard',
            'permission' => 'ver dashboard'
        ];

        // Admin y Coordinador tienen acceso completo
        if ($user->hasRole(['administrador', 'coordinador'])) {
            $menu[] = [
                'title' => 'GESTIÓN ACADÉMICA',
                'type' => 'header'
            ];
            
            $menu[] = [
                'title' => 'Carreras',
                'icon' => 'fas fa-graduation-cap',
                'route' => 'carrera.index',
                'permission' => 'ver carreras'
            ];
            
            $menu[] = [
                'title' => 'Alumnos',
                'icon' => 'fas fa-user-graduate',
                'route' => 'alumno.index',
                'permission' => 'ver alumnos'
            ];
            
            $menu[] = [
                'title' => 'Tutores',
                'icon' => 'fas fa-chalkboard-teacher',
                'route' => 'tutor.index',
                'permission' => 'ver tutores'
            ];
        }

        // Tesis - todos los roles pueden ver
        $menu[] = [
            'title' => 'Tesis',
            'icon' => 'fas fa-book',
            'route' => 'tesis.index',
            'permission' => 'ver tesis'
        ];

        // Documentos - todos los roles pueden ver
        $menu[] = [
            'title' => 'Documentos',
            'icon' => 'fas fa-file-alt',
            'route' => 'documento.index',
            'permission' => 'ver documentos'
        ];

        // Proyectos - Admin, Coordinador y Alumno
        if ($user->hasRole(['administrador', 'coordinador', 'alumno'])) {
            $menu[] = [
                'title' => 'Proyectos',
                'icon' => 'fas fa-project-diagram',
                'route' => 'proyecto.index',
                'permission' => 'ver proyectos'
            ];
        }

        // Solo Admin y Coordinador ven herramientas de desarrollo
        if ($user->hasRole(['administrador', 'coordinador'])) {
            $menu[] = [
                'title' => 'HERRAMIENTAS DE DESARROLLO',
                'type' => 'header'
            ];
            
            $menu[] = [
                'title' => 'Monitor de Formularios',
                'icon' => 'fas fa-clipboard-list',
                'route' => 'debug.form.monitor'
            ];
            
            $menu[] = [
                'title' => 'Diagnóstico DB',
                'icon' => 'fas fa-database',
                'route' => 'debug.database.info'
            ];
        }

        // Solo Admin ve reportes y configuración
        if ($user->hasRole('administrador')) {
            $menu[] = [
                'title' => 'REPORTES',
                'type' => 'header'
            ];
            
            $menu[] = [
                'title' => 'Exportar Datos',
                'icon' => 'fas fa-download',
                'route' => 'carrera.export.excel' // Placeholder
            ];
            
            $menu[] = [
                'title' => 'CONFIGURACIÓN',
                'type' => 'header'
            ];
        }

        return $menu;
    }
}
