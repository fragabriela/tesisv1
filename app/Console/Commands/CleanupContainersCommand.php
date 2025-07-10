<?php

namespace App\Console\Commands;

use App\Services\DockerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupContainersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docker:cleanup {--days=7 : Number of days of inactivity before cleanup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup inactive Docker containers for thesis projects';

    /**
     * The Docker service instance.
     *
     * @var \App\Services\DockerService
     */
    protected $dockerService;

    /**
     * Create a new command instance.
     */
    public function __construct(DockerService $dockerService)
    {
        parent::__construct();
        $this->dockerService = $dockerService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $this->info("Starting cleanup of containers inactive for {$days} days or more...");
        
        try {
            $results = $this->dockerService->cleanupInactiveContainers($days);
            
            $this->info("Cleanup completed:");
            $this->info("- Containers stopped: {$results['stopped']}");
            $this->info("- Containers removed: {$results['removed']}");
            
            if (!empty($results['errors'])) {
                $this->warn("Errors encountered:");
                foreach ($results['errors'] as $error) {
                    $this->error("- {$error}");
                }
            }
            
            Log::info("Container cleanup completed. Stopped: {$results['stopped']}, Removed: {$results['removed']}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Error during cleanup: {$e->getMessage()}");
            Log::error("Error during container cleanup: {$e->getMessage()}");
            return 1;
        }
    }
}
