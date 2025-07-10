<?php

namespace App\Console\Commands;

use Database\Seeders\FixEstadoValuesSeeder;
use Illuminate\Console\Command;

class FixTesisEstadoValues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-tesis-estado';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix inconsistent estado values in tesis table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to fix tesis estado values...');
        
        $seeder = new FixEstadoValuesSeeder();
        $seeder->setCommand($this);
        $seeder->run();
        
        $this->info('Tesis estado values have been fixed!');
        
        return Command::SUCCESS;
    }
}
