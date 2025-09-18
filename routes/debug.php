<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-dashboard-permission', function() {
    if (!auth()->check()) {
        return response()->json([
            'error' => 'Usuario no autenticado',
            'redirect' => route('login')
        ]);
    }
    
    $user = auth()->user();
    
    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email
        ],
        'roles' => $user->roles->pluck('name'),
        'permissions' => $user->getAllPermissions()->pluck('name'),
        'can_ver_dashboard' => $user->can('ver dashboard'),
        'should_access_dashboard' => $user->can('ver dashboard') ? 'SÍ' : 'NO',
        'message' => $user->can('ver dashboard') 
            ? 'El usuario puede acceder al dashboard' 
            : 'El usuario NO puede acceder al dashboard - debería ver error 403'
    ]);
})->name('test.dashboard.permission');