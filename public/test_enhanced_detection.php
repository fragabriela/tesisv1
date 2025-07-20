<?php
// Simple script to test our enhanced DockerService detection
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\DockerService;
use App\Models\Tesis;
use Illuminate\Support\Facades\Log;

echo "Testing enhanced Docker container detection...\n";

// Create a docker service
$dockerService = new DockerService();

// Create a test tesis with the problematic path
$tesis = new Tesis();
$tesis->id = 999;
$tesis->title = 'Problematic Test';
$tesis->project_repo_path = 'repos/iYPDVbLBw0';
$tesis->project_type = 'php';

// Try to build and run the project
echo "Calling buildAndRunProject for problematic repo...\n";
$result = $dockerService->buildAndRunProject($tesis);

// Check the result
if ($result === null) {
    echo "ERROR: Failed to detect container.\n";
} else {
    echo "SUCCESS: Container detected!\n";
    echo "Container ID: " . $result['container_id'] . "\n";
    echo "Port: " . $result['port'] . "\n";
    echo "URL: http://localhost:" . $result['port'] . "\n";
}

// Try to connect to the container
if ($result !== null && isset($result['port'])) {
    echo "\nTesting connection to container...\n";
    
    $url = "http://localhost:" . $result['port'];
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $response = @file_get_contents($url, false, $context);
    
    if ($response !== false) {
        echo "SUCCESS: Container is accessible\n";
        echo "Response: " . substr($response, 0, 100) . "...\n";
    } else {
        echo "ERROR: Could not connect to container\n";
    }
}

echo "\nTest completed!\n";
