<?php

// Function to download a file
function downloadFile($url, $savePath) {
    echo "Downloading {$url} to {$savePath}\n";
    $content = file_get_contents($url);
    if ($content === false) {
        echo "Failed to download: {$url}\n";
        return false;
    }
    
    if (file_put_contents($savePath, $content) === false) {
        echo "Failed to save to: {$savePath}\n";
        return false;
    }
    
    echo "Successfully downloaded to {$savePath}\n";
    return true;
}

// Create directories if they don't exist
$dirs = [
    __DIR__ . '/public/vendor',
    __DIR__ . '/public/vendor/datatables',
    __DIR__ . '/public/vendor/datatables/css',
    __DIR__ . '/public/vendor/datatables/js',
    __DIR__ . '/public/vendor/toastr',
];

foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
        echo "Created directory: {$dir}\n";
    }
}

// Files to download
$files = [
    'https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css' => __DIR__ . '/public/vendor/datatables/css/dataTables.bootstrap4.min.css',
    'https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css' => __DIR__ . '/public/vendor/datatables/css/responsive.bootstrap4.min.css',
    'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js' => __DIR__ . '/public/vendor/datatables/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js' => __DIR__ . '/public/vendor/datatables/js/dataTables.bootstrap4.min.js',
    'https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js' => __DIR__ . '/public/vendor/datatables/js/dataTables.responsive.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css' => __DIR__ . '/public/vendor/toastr/toastr.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js' => __DIR__ . '/public/vendor/toastr/toastr.min.js',
];

// Download each file
foreach ($files as $url => $savePath) {
    downloadFile($url, $savePath);
}

echo "All files downloaded.\n";
