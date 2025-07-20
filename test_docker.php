<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Docker connection...\n";

$dockerService = app(\App\Services\DockerService::class);
$available = $dockerService->checkDockerAvailability();

if ($available) {
    echo "Docker is available and working!\n";
    
    // Run a basic Docker command to get version info
    exec('docker --version', $output);
    echo "Docker Version: " . implode("\n", $output) . "\n";
    
    // Check if any containers are running
    exec('docker ps', $containerOutput);
    echo "\nRunning Containers:\n";
    foreach ($containerOutput as $line) {
        echo $line . "\n";
    }
} else {
    echo "Docker is not available. Please make sure Docker Desktop is running.\n";
}
