<?php
echo "Step 1: Init mysqli<br>";
$conn = mysqli_init();
echo "Step 2: Set timeout<br>";
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
echo "Step 3: Real connect...<br>";
$start = microtime(true);
if (!$conn->real_connect("127.0.0.1", "root", "", "", 3306)) {
    die("Connect failed: " . mysqli_connect_error());
}
echo "Connected in " . (microtime(true) - $start) . " seconds.<br>";

echo "Step 4: Create DB if not exists...<br>";
$start = microtime(true);
$conn->query("CREATE DATABASE IF NOT EXISTS healcare");
echo "Query took " . (microtime(true) - $start) . " seconds.<br>";

echo "Step 5: Select DB...<br>";
$conn->select_db("healcare");

echo "Step 6: Show tables...<br>";
$start = microtime(true);
$res = $conn->query("SHOW TABLES LIKE 'users'");
echo "Query took " . (microtime(true) - $start) . " seconds.<br>";

echo "Done.";
?>
