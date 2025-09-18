<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ListUsers extends Command
{
    protected $signature = 'users:list';
    protected $description = 'List all users with their roles';

    public function handle()
    {
        $users = User::with('roles')->get();
        
        $this->info('=== USUARIOS REGISTRADOS ===');
        foreach ($users as $user) {
            $roles = $user->roles->pluck('name')->join(', ');
            $this->line("{$user->name} - {$user->email} - Roles: {$roles}");
        }
        
        return 0;
    }
}