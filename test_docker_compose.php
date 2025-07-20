<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Docker Compose File Generation\n";
echo "====================================\n\n";

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

// Create test directory
$testDir = storage_path('app/docker_test_2');
if (!file_exists($testDir)) {
    mkdir($testDir, 0777, true);
}

// Create simple Dockerfile
file_put_contents($testDir . '/Dockerfile', "FROM php:8.1-apache\n\nEXPOSE 80");

// Create docker-compose.yml
$containerName = 'test-container-' . Str::random(5);

$composeYml = <<<EOT
version: '3'
services:
  app:
    build: .
    container_name: {$containerName}
    restart: unless-stopped
    ports:
      - "8082:80"
    environment:
      APP_ENV: production
      APP_DEBUG: 'false'
EOT;

file_put_contents($testDir . '/docker-compose.yml', $composeYml);

// Try to run the container
echo "Testing docker-compose.yml in $testDir\n\n";
echo "docker-compose.yml content:\n" . file_get_contents($testDir . '/docker-compose.yml') . "\n\n";

$cmd = "cd $testDir && docker compose up -d 2>&1";
echo "Executing command: $cmd\n";
exec($cmd, $output, $returnVar);

if ($returnVar === 0) {
    echo "Success! Docker Compose worked correctly.\n";
    
    // Stop and remove the container
    exec("docker stop $containerName && docker rm $containerName");
    echo "Container stopped and removed.\n";
} else {
    echo "Error running Docker Compose: (code $returnVar)\n";
    echo implode("\n", $output) . "\n";
    
    // Try with docker-compose instead
    $cmd = "cd $testDir && docker-compose up -d 2>&1";
    echo "\nTrying with docker-compose: $cmd\n";
    exec($cmd, $output2, $returnVar2);
    
    if ($returnVar2 === 0) {
        echo "Success with docker-compose command!\n";
        
        // Stop and remove the container
        exec("docker stop $containerName && docker rm $containerName");
        echo "Container stopped and removed.\n";
    } else {
        echo "Error running docker-compose: (code $returnVar2)\n";
        echo implode("\n", $output2) . "\n";
    }
}

// Clean up
echo "\nCleaning up test files...\n";
unlink($testDir . '/Dockerfile');
unlink($testDir . '/docker-compose.yml');
rmdir($testDir);
echo "Test files removed.\n";
