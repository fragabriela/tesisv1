<?php
// Simple log viewer for Laravel
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

// Path to Laravel log file
$logFile = __DIR__ . '/../storage/logs/laravel.log';

// Check if file exists
if (!file_exists($logFile)) {
    echo "data: Log file not found at $logFile\n\n";
    exit;
}

// Get current file size
$currentSize = filesize($logFile);

// Send the current size as the first message
echo "data: Starting log monitor... Current log size: " . $currentSize . " bytes\n\n";
flush();

// Initial delay to ensure browser connects
sleep(1);

// Check for changes every 2 seconds
while (true) {
    // Check if file has been modified
    clearstatcache(true, $logFile);
    $newSize = filesize($logFile);
    
    if ($newSize > $currentSize) {
        // File has grown, read the new content
        $handle = fopen($logFile, 'r');
        fseek($handle, $currentSize);
        $newContent = fread($handle, $newSize - $currentSize);
        fclose($handle);
        
        // Send the new content
        echo "data: " . str_replace("\n", "\ndata: ", $newContent) . "\n\n";
        flush();
        
        $currentSize = $newSize;
    }
    
    // Wait before checking again
    sleep(2);
}
?>
