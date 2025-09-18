<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @return string
     */
    protected function redirectTo()
    {
        $user = auth()->user();
        
        // Si puede ver dashboard, ir al dashboard
        if ($user->can('ver dashboard')) {
            return '/dashboard';
        }
        
        // Si no puede ver dashboard, redirigir al primer módulo disponible
        $availableModules = [
            'ver tesis' => '/tesis',
            'ver documentos' => '/documento', 
            'ver proyectos' => '/proyectos',
            'ver alumnos' => '/alumno',
            'ver carreras' => '/carrera',
            'ver tutores' => '/tutor',
        ];
        
        foreach ($availableModules as $permission => $route) {
            if ($user->can($permission)) {
                return $route;
            }
        }
        
        // Si no tiene permisos para ningún módulo, mostrar mensaje
        return '/no-permissions';
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return $this->loggedOut($request) ?: redirect('/login');
    }
}
