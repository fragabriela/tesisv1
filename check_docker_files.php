<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get repository path from tesis record
$tesis = \App\Models\Tesis::first();

if (!$tesis || empty($tesis->project_repo_path)) {
    echo "No tesis found or no repository path set.\n";
    exit(1);
}

$repoPath = storage_path('app/public/' . $tesis->project_repo_path);
$dockerfilePath = $repoPath . '/Dockerfile';
$composePath = $repoPath . '/docker-compose.yml';

echo "Checking files in $repoPath:\n";
echo "Dockerfile exists: " . (file_exists($dockerfilePath) ? "Yes" : "No") . "\n";
echo "docker-compose.yml exists: " . (file_exists($composePath) ? "Yes" : "No") . "\n\n";

if (file_exists($composePath)) {
    echo "Contents of docker-compose.yml:\n";
    echo file_get_contents($composePath) . "\n\n";
}

if (file_exists($dockerfilePath)) {
    echo "Contents of Dockerfile:\n";
    echo file_get_contents($dockerfilePath) . "\n\n";
}

// Let's make a simplified docker-compose.yml
echo "Creating simplified docker-compose.yml...\n";
$simplifiedCompose = <<<EOT
version: '3'
services:
  app:
    build: .
    container_name: test-project-simple
    ports:
      - "8083:80"
EOT;

file_put_contents($composePath, $simplifiedCompose);
echo "Simplified docker-compose.yml created.\n";
