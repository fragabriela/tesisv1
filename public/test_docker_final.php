<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap/app.php';

use App\Services\DockerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Function to create a test project
function createTestProject($projectName) {
    $testPath = 'repos/' . $projectName;
    $fullPath = storage_path('app/public/' . $testPath);
    
    if (file_exists($fullPath)) {
        echo "Test project already exists at: $fullPath\n";
        return $testPath;
    }
    
    // Create directory structure
    mkdir($fullPath, 0777, true);
    mkdir($fullPath . '/public', 0777, true);
    
    // Create a simple index.php file
    file_put_contents($fullPath . '/public/index.php', '<?php echo "<h1>Docker Test Project</h1><p>Container is running successfully!</p>";');
    
    // Create a simple composer.json file to identify as a PHP project
    file_put_contents($fullPath . '/composer.json', '{
        "name": "test/docker-project",
        "description": "Test project for Docker deployment",
        "type": "project",
        "require": {
            "php": "^8.0"
        }
    }');
    
    echo "Test project created at: $fullPath\n";
    return $testPath;
}

// Main test function
function testDockerService() {
    echo "Testing DockerService implementation...\n";
    
    // Create the test project
    $projectName = 'test_project_' . Str::random(5);
    $projectPath = createTestProject($projectName);
    
    // Initialize the DockerService
    $dockerService = new DockerService();
    
    // Check if Docker is available
    echo "Checking Docker availability...\n";
    if (!$dockerService->checkDockerAvailability()) {
        echo "ERROR: Docker is not available\n";
        return false;
    }
    echo "SUCCESS: Docker is available\n";
    
    // Create Dockerfile for the test project
    echo "Creating Dockerfile...\n";
    if (!$dockerService->createDockerfile($projectPath, 'php')) {
        echo "ERROR: Failed to create Dockerfile\n";
        return false;
    }
    echo "SUCCESS: Dockerfile created\n";
    
    // Create docker-compose.yml for the test project
    echo "Creating docker-compose.yml...\n";
    if (!$dockerService->createDockerCompose($projectPath, 'php')) {
        echo "ERROR: Failed to create docker-compose.yml\n";
        return false;
    }
    echo "SUCCESS: docker-compose.yml created\n";
    
    // Display the contents of the generated files
    $fullPath = storage_path('app/public/' . $projectPath);
    echo "\nGenerated Dockerfile:\n";
    echo file_get_contents($fullPath . '/Dockerfile');
    
    echo "\nGenerated docker-compose.yml:\n";
    echo file_get_contents($fullPath . '/docker-compose.yml');
    
    // Run Docker command to build and start the container
    echo "\nBuilding and running the Docker container...\n";
    $cmd = "cd $fullPath && docker compose up -d --build";
    echo "Executing: $cmd\n";
    exec($cmd, $output, $returnVar);
    
    if ($returnVar !== 0) {
        echo "ERROR: Failed to build and run Docker container\n";
        echo "Output: " . implode("\n", $output) . "\n";
        return false;
    }
    
    echo "SUCCESS: Docker container built and running\n";
    echo "Output: " . implode("\n", $output) . "\n";
    
    // Get container information
    $cmd = "docker ps --filter 'name=" . $projectName . "' --format '{{.ID}}|{{.Names}}|{{.Ports}}'";
    exec($cmd, $containerInfo);
    
    if (empty($containerInfo)) {
        echo "ERROR: Could not find running container\n";
        return false;
    }
    
    echo "\nContainer information:\n";
    foreach ($containerInfo as $info) {
        echo "$info\n";
        
        // Extract port information
        list($containerId, $containerName, $ports) = explode('|', $info);
        preg_match('/0.0.0.0:(\d+)/', $ports, $matches);
        $port = $matches[1] ?? null;
        
        if ($port) {
            echo "\nContainer is accessible at: http://localhost:$port\n";
        }
    }
    
    echo "\nTest completed successfully!\n";
    return true;
}

// Run the test
testDockerService();
