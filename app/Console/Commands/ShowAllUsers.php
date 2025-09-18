<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ShowAllUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all users with their roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::with('roles')->get();
        
        $this->info("Lista de usuarios:");
        $this->line("");
        
        foreach ($users as $user) {
            $roles = $user->roles->pluck('name')->join(', ') ?: 'Sin roles';
            $this->info("ID: {$user->id} | Nombre: {$user->name} | Email: {$user->email} | Roles: {$roles}");
        }
    }
}
