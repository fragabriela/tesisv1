<?php
// Test script to verify Docker container detection with problematic repo name
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\DockerService;
use App\Models\Tesis;
use Illuminate\Support\Facades\Log;

// Create a test Tesis model with the problematic repository name
$testTesis = new Tesis();
$testTesis->id = 999;
$testTesis->title = 'Test Thesis with Problematic Name';
$testTesis->project_type = 'php';
$testTesis->project_repo_path = 'repos/iYPDVbLBw0'; // Problematic repo name from logs

echo "Testing with problematic repo name: {$testTesis->project_repo_path}\n";

// Create a Docker service instance
$dockerService = new DockerService();

// Check if Docker is available
echo "Checking Docker availability...\n";
if (!$dockerService->checkDockerAvailability()) {
    echo "ERROR: Docker is not available\n";
    exit(1);
}

echo "SUCCESS: Docker is available\n";

// Get information about existing containers
echo "\nCurrent Docker containers:\n";
exec('docker ps', $containers);
echo implode("\n", $containers) . "\n\n";

// Check if there's an existing container for this repo
echo "Checking for existing container for this repo...\n";

// Get more detailed information
exec('docker ps --format "{{.ID}}|{{.Image}}|{{.Names}}|{{.Ports}}"', $containerDetails);
echo "Container details:\n";
$foundContainer = false;
$containerId = null;
$containerPorts = null;

foreach ($containerDetails as $container) {
    echo "$container\n";
    
    // Check if this container matches our repo
    $parts = explode('|', $container);
    if (count($parts) >= 3) {
        $id = $parts[0];
        $image = $parts[1];
        $name = $parts[2];
        $ports = $parts[3] ?? '';
        
        if (stripos($name, 'iYPDVbLBw0') !== false || stripos($image, 'iYPDVbLBw0') !== false) {
            echo "\nFound matching container!\n";
            echo "ID: $id\n";
            echo "Image: $image\n";
            echo "Name: $name\n";
            echo "Ports: $ports\n";
            $foundContainer = true;
            $containerId = $id;
            $containerPorts = $ports;
        }
    }
}

if (!$foundContainer) {
    echo "\nNo existing container found. Will try to create one.\n";
    
    // Deploy the project (this should reuse the existing Dockerfile if present)
    echo "Deploying test project with Docker...\n";
    $result = $dockerService->buildAndRunProject($testTesis);
    
    // Check the result
    if ($result === null) {
        echo "ERROR: Deployment failed. Check Laravel logs for details.\n";
        
        // Get last few log entries
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            $logs = file($logPath);
            $lastLogs = array_slice($logs, -15);
            echo "\nRecent log entries:\n";
            echo implode("", $lastLogs);
        }
        
        exit(1);
    }
    
    // Display success information
    echo "SUCCESS: Project deployed successfully!\n";
    echo "Container ID: " . $result['container_id'] . "\n";
    echo "Status: " . $result['container_status'] . "\n";
    echo "Port: " . $result['port'] . "\n";
    echo "Access URL: http://localhost:" . $result['port'] . "\n";
} else {
    echo "\nManually testing container detection logic...\n";
    
    // Extract port from container ports
    $port = null;
    
    // Formato IPv4: 0.0.0.0:32774->80/tcp
    if (preg_match('/0.0.0.0:(\d+)/', $containerPorts, $matches)) {
        $port = $matches[1];
        echo "Puerto encontrado (formato IPv4): $port\n";
    } 
    // Formato IPv6: [::]:32774->80/tcp
    elseif (preg_match('/\[\:\:\]:(\d+)/', $containerPorts, $matches)) {
        $port = $matches[1];
        echo "Puerto encontrado (formato IPv6): $port\n";
    }
    // Formato alternativo: *:32774->80/tcp
    elseif (preg_match('/:(\d+)->/', $containerPorts, $matches)) {
        $port = $matches[1];
        echo "Puerto encontrado (formato alternativo): $port\n";
    }
    
    if (!$port) {
        echo "ERROR: Could not determine port from: $containerPorts\n";
    } else {
        echo "Port detected: $port\n";
        echo "Container accessible at: http://localhost:$port\n";
    }
}

echo "\nTest completed!\n";
