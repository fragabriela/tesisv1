<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Redirect user to appropriate module based on permissions
     */
    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        $user = auth()->user();
        
        // Si puede ver dashboard, ir al dashboard
        if ($user->can('ver dashboard')) {
            return redirect('/dashboard');
        }
        
        // Si no puede ver dashboard, redirigir al primer módulo disponible
        $availableModules = [
            'ver tesis' => [
                'route' => '/tesis',
                'name' => 'Tesis'
            ],
            'ver documentos' => [
                'route' => '/documento',
                'name' => 'Documentos'
            ],
            'ver proyectos' => [
                'route' => '/proyectos',
                'name' => 'Proyectos'
            ],
            'ver alumnos' => [
                'route' => '/alumno',
                'name' => 'Alumnos'
            ],
            'ver carreras' => [
                'route' => '/carrera',
                'name' => 'Carreras'
            ],
            'ver tutores' => [
                'route' => '/tutor',
                'name' => 'Tutores'
            ],
        ];
        
        foreach ($availableModules as $permission => $module) {
            if ($user->can($permission)) {
                return redirect($module['route'])->with('info', "Bienvenido! Has sido redirigido al módulo de {$module['name']} ya que no tienes permisos para acceder al dashboard.");
            }
        }
        
        // Si no tiene permisos para ningún módulo
        return view('no-permissions');
    }
    
    /**
     * Show no permissions page
     */
    public function noPermissions()
    {
        return view('no-permissions');
    }
}