<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckUserPermission extends Command
{
    protected $signature = 'user:check-permission {email} {permission}';
    protected $description = 'Check if a user has a specific permission';

    public function handle()
    {
        $email = $this->argument('email');
        $permission = $this->argument('permission');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("Usuario con email '{$email}' no encontrado.");
            return;
        }

        $this->info("Usuario: {$user->name} ({$user->email})");
        $this->info("Verificando permiso: {$permission}");
        
        $hasPermission = $user->can($permission);
        
        if ($hasPermission) {
            $this->info("✅ El usuario SÍ tiene el permiso '{$permission}'");
        } else {
            $this->error("❌ El usuario NO tiene el permiso '{$permission}'");
        }
        
        $this->info("Todos los permisos del usuario:");
        foreach ($user->getAllPermissions() as $perm) {
            $this->line("- " . $perm->name);
        }
    }
}