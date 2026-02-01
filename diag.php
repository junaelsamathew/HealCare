<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'includes/db_connect.php';

echo "<h1>Diagnostic Report</h1>";

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "✅ Database connected.<br>";

// Check tables
$tables = ['appointments', 'doctors', 'users', 'registrations', 'external_patients'];
foreach ($tables as $t) {
    $res = $conn->query("SHOW TABLES LIKE '$t'");
    if ($res && $res->num_rows > 0) {
        echo "✅ Table '$t' exists.<br>";
        $cols = $conn->query("DESCRIBE $t");
        echo "<ul>";
        while($c = $cols->fetch_assoc()) { echo "<li>" . $c['Field'] . " (" . $c['Type'] . ")</li>"; }
        echo "</ul>";
    } else {
        echo "❌ Table '$t' MISSING.<br>";
    }
}

echo "<h2>Session Check</h2>";
session_start();
echo "<pre>"; print_r($_SESSION); echo "</pre>";

if (!isset($_SESSION['logged_in'])) {
    echo "⚠️ Not logged in.<br>";
} else if ($_SESSION['user_role'] != 'doctor') {
    echo "⚠️ Role is " . $_SESSION['user_role'] . " (Doctor required).<br>";
} else {
    echo "✅ Authenticated as Doctor.<br>";
}
?>
