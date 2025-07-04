<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ForceRunMigrations extends Command
{
    protected $signature = 'migrate:force-run';
    protected $description = 'Force run all migrations';

    public function handle()
    {
        $this->info('Force running all migrations...');
        
        try {
            // Run migrations with force flag
            $this->info('Running with --force');
            Artisan::call('migrate', [
                '--force' => true
            ]);
            
            $this->info(Artisan::output());
            $this->info('All migrations have been run successfully.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
