<?php
// Test access to Docker container
$url = 'http://localhost:32768';
echo "Testing access to Docker container at $url\n";

try {
    // Set timeout for the request
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
        echo "HTTP response headers: " . print_r($http_response_header, true) . "\n";
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
