<?php
// Simple form submission test file

echo "<h1>Form Submission Test</h1>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";

echo "<h2>POST Data</h2>";
echo "<pre>";
var_dump($_POST);
echo "</pre>";

echo "<h2>REQUEST Method</h2>";
echo $_SERVER['REQUEST_METHOD'];

echo "<h2>PUT Data (parsed from php://input if method is PUT)</h2>";
$putdata = file_get_contents('php://input');
echo "<pre>";
var_dump($putdata);
echo "</pre>";

// Log this data
file_put_contents(
    __DIR__ . '/../storage/logs/form-test-' . date('Y-m-d-H-i-s') . '.log', 
    "Method: " . $_SERVER['REQUEST_METHOD'] . "\n" .
    "POST: " . print_r($_POST, true) . "\n" .
    "PUT: " . $putdata . "\n"
);

echo "<h3>Form for testing</h3>";
?>
<form action="/formtest.php" method="POST">
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="_token" value="test-token">
    <input type="text" name="test_value" value="Test Value">
    <button type="submit">Submit</button>
</form>
