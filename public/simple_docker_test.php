<?php
// Simple test script for Docker functionality
$testDir = 'test_project_' . rand(1000, 9999);
$testPath = __DIR__ . '/../storage/app/public/repos/' . $testDir;

echo "Creating test project directory: $testPath\n";
mkdir($testPath, 0777, true);
mkdir($testPath . '/public', 0777, true);

// Create a simple index.php file
file_put_contents($testPath . '/public/index.php', '<?php echo "<h1>Docker Test Project</h1><p>Container is running successfully!</p>";');

// Create a composer.json file
file_put_contents($testPath . '/composer.json', '{
    "name": "test/docker-project",
    "description": "Test project for Docker deployment",
    "type": "project",
    "require": {
        "php": "^8.0"
    }
}');

// Create a Dockerfile
$dockerfile = <<<'EOT'
FROM php:8.1-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git

# Configure PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo_mysql gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Apache to use the directory /var/www/html/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN a2enmod rewrite

# Copy project
WORKDIR /var/www/html
COPY . .

# Configure permissions
RUN if [ -d "storage" ]; then \
        chown -R www-data:www-data /var/www/html/storage; \
        chmod -R 775 /var/www/html/storage; \
    fi

EXPOSE 80
EOT;

echo "Creating Dockerfile...\n";
file_put_contents($testPath . '/Dockerfile', $dockerfile);

// Create docker-compose.yml
$containerName = 'test-project-' . rand(1000, 9999);
$dockerCompose = <<<EOT
version: '3'
services:
  app:
    build: .
    container_name: {$containerName}
    restart: unless-stopped
    ports:
      - "0:80"
    environment:
      APP_ENV: production
      APP_DEBUG: 'false'
      DB_CONNECTION: sqlite
      DB_DATABASE: ':memory:'
EOT;

echo "Creating docker-compose.yml...\n";
file_put_contents($testPath . '/docker-compose.yml', $dockerCompose);

// Display file contents
echo "\nGenerated Dockerfile:\n";
echo file_get_contents($testPath . '/Dockerfile');

echo "\nGenerated docker-compose.yml:\n";
echo file_get_contents($testPath . '/docker-compose.yml');

// Test Docker availability
echo "\nChecking Docker availability...\n";
exec('docker --version 2>&1', $output, $returnVar);
if ($returnVar !== 0) {
    echo "ERROR: Docker is not available\n";
    echo "Output: " . implode("\n", $output) . "\n";
    exit(1);
}
echo "SUCCESS: Docker is available: " . implode("\n", $output) . "\n";

// Run Docker command
echo "\nBuilding and running the Docker container...\n";
$cmd = "cd $testPath && docker compose up -d --build";
echo "Executing: $cmd\n";
exec($cmd, $output, $returnVar);

if ($returnVar !== 0) {
    echo "ERROR: Failed to build and run Docker container\n";
    echo "Output: " . implode("\n", $output) . "\n";
    exit(1);
}

echo "SUCCESS: Docker container built and running\n";
echo "Output: " . implode("\n", $output) . "\n";

// Get container information
echo "\nGetting container information...\n";
$cmd = "docker ps --filter 'name=" . $containerName . "' --format '{{.ID}}|{{.Names}}|{{.Ports}}'";
exec($cmd, $containerInfo);

if (empty($containerInfo)) {
    echo "ERROR: Could not find running container\n";
    exit(1);
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
