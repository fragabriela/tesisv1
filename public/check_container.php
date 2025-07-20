<?php
// Test access to Docker container
$url = 'http://localhost:32768';
echo "Testing access to Docker container at $url\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Response Code: $httpCode\n";
if ($httpCode === 200) {
    echo "SUCCESS: Container is accessible\n";
    echo "Response content:\n$response\n";
} else {
    echo "ERROR: Could not access container\n";
}
