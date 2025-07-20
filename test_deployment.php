<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\DockerService;
use Illuminate\Support\Facades\Log;

echo "Testing Docker deployment for Laravel project...\n";

// Get a test project
$tesis = \App\Models\Tesis::first();

if (!$tesis) {
    echo "No Tesis model found in database. Please create one first.\n";
    exit(1);
}

// Setup test data if needed
if (empty($tesis->project_type)) {
    echo "Setting project type to 'laravel' for testing...\n";
    $tesis->project_type = 'laravel';
}

if (empty($tesis->project_repo_path)) {
    echo "Creating a test repository path...\n";
    
    // Create a test directory
    $testDir = 'repos/test_project_' . time();
    $fullPath = storage_path('app/public/' . $testDir);
    
    if (!file_exists(dirname($fullPath))) {
        mkdir(dirname($fullPath), 0777, true);
    }
    
    // Create a simple Laravel-like structure
    mkdir($fullPath, 0777, true);
    file_put_contents($fullPath . '/composer.json', '{"require": {"laravel/framework": "^10.0"}}');
    file_put_contents($fullPath . '/artisan', '#!/usr/bin/env php');
    
    $tesis->project_repo_path = $testDir;
}

// Save changes
$tesis->save();

echo "Using project: {$tesis->titulo} (ID: {$tesis->id})\n";
echo "Project type: " . ($tesis->project_type ?? 'Not specified') . "\n";
echo "Repository path: " . ($tesis->project_repo_path ?? 'Not specified') . "\n";
echo "GitHub repo: " . ($tesis->github_repo ?? 'Not specified') . "\n";

// Create Docker service
$dockerService = new DockerService();

// Deploy the project
echo "Attempting to deploy the project...\n";
try {
    $result = $dockerService->buildAndRunProject($tesis);
    
    if ($result) {
        echo "Success! Project deployed successfully.\n";
        echo "Container ID: {$result['container_id']}\n";
        echo "Status: {$result['container_status']}\n";
        echo "External Port: {$result['project_config']['external_port']}\n";
        echo "Access URL: {$result['project_url']}\n";
        
        // Update the Tesis model
        $tesis->container_id = $result['container_id'];
        $tesis->container_status = $result['container_status'];
        $tesis->project_url = $result['project_url'];
        $tesis->project_config = $result['project_config'];
        $tesis->last_deployed = now();
        $tesis->save();
        
        echo "Tesis record updated with deployment details.\n";
    } else {
        echo "Deployment failed. No result returned.\n";
    }
} catch (\Exception $e) {
    echo "Error during deployment: {$e->getMessage()}\n";
    Log::error("Error testing deployment: " . $e->getMessage());
}
