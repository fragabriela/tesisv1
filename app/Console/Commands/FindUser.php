<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class FindUser extends Command
{
    protected $signature = 'user:find {search}';
    protected $description = 'Find users by name or email';

    public function handle()
    {
        $search = $this->argument('search');
        
        $users = User::where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->with('roles', 'permissions')
                    ->get();

        if ($users->isEmpty()) {
            $this->error("No se encontraron usuarios con '{$search}'");
            return;
        }

        foreach ($users as $user) {
            $this->info("Usuario: {$user->name} ({$user->email})");
            $this->info("ID: {$user->id}");
            $this->info("Roles: " . $user->roles->pluck('name')->join(', '));
            $this->info("Permisos directos: " . $user->permissions->pluck('name')->join(', '));
            $this->info("Todos los permisos: " . $user->getAllPermissions()->pluck('name')->join(', '));
            $this->info("---");
        }
    }
}