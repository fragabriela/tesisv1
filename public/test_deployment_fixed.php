<?php
// Test script to verify Docker container deployment

// Include the autoloader
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap the Laravel application
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\DockerService;
use App\Models\Tesis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Create a test Tesis model with necessary properties
$testTesis = new Tesis();
$testTesis->id = 999; // Test ID
$testTesis->title = 'Test Thesis';
$testTesis->project_type = 'php';

// Create a unique test project path
$testDir = 'test_thesis_' . Str::random(8);
$testTesis->project_repo_path = 'repos/' . $testDir;

// Create the test project directory
$fullPath = storage_path('app/public/' . $testTesis->project_repo_path);
if (!file_exists($fullPath)) {
    mkdir($fullPath, 0777, true);
    mkdir($fullPath . '/public', 0777, true);

    // Create test files
    file_put_contents($fullPath . '/public/index.php', '<?php echo "<h1>Test Thesis Project</h1><p>Docker deployment successful!</p>";');
    file_put_contents($fullPath . '/composer.json', json_encode([
        'name' => 'test/thesis-project',
        'description' => 'Test project for Docker deployment',
        'type' => 'project',
        'require' => [
            'php' => '^8.0'
        ]
    ], JSON_PRETTY_PRINT));

    echo "Test project created at: $fullPath\n";
} else {
    echo "Using existing test project at: $fullPath\n";
}

// Create a Docker service instance
$dockerService = new DockerService();

// Check Docker availability
echo "Checking Docker availability...\n";
if (!$dockerService->checkDockerAvailability()) {
    echo "ERROR: Docker is not available\n";
    exit(1);
}
echo "SUCCESS: Docker is available\n";

// Deploy the project
echo "Deploying test project with Docker...\n";
$result = $dockerService->buildAndRunProject($testTesis);

// Check the result
if ($result === null) {
    echo "ERROR: Deployment failed. Check Laravel logs for details.\n";
    echo "Recent log entries:\n";
    
    // Get last few log entries
    $logPath = storage_path('logs/laravel.log');
    if (file_exists($logPath)) {
        $logs = file($logPath);
        $lastLogs = array_slice($logs, -10);
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

// Try to connect to the service
echo "\nTesting connection to deployed service...\n";
$url = "http://localhost:" . $result['port'];

try {
    $context = stream_context_create([
        'http' => [
            'timeout' => 5
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response !== false) {
        echo "SUCCESS: Container is accessible\n";
        echo "Response content:\n$response\n";
    } else {
        echo "ERROR: Could not access container\n";
        if (isset($http_response_header)) {
            echo "HTTP response headers: " . print_r($http_response_header, true) . "\n";
        }
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\nTest completed!\n";
