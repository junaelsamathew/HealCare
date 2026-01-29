<?php
require 'includes/db_connect.php';
$tables = ['appointments', 'medical_records', 'lab_tests', 'billing', 'prescriptions'];
$output = "";
foreach ($tables as $table) {
    $output .= "--- $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while($row = $res->fetch_assoc()) {
            $output .= $row['Field'] . " - " . $row['Type'] . "\n";
        }
    } else {
        $output .= "Table not found or error: " . $conn->error . "\n";
    }
    $output .= "\n";
}
file_put_contents('schema_dump.txt', $output);
?>
