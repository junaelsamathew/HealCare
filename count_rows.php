<?php
include 'includes/db_connect.php';

$tables = ['users', 'registrations', 'patient_profiles', 'appointments', 'billing', 'doctors'];

foreach ($tables as $table) {
    $res = $conn->query("SELECT COUNT(*) as count FROM $table");
    if ($res) {
        $row = $res->fetch_assoc();
        echo "$table: " . $row['count'] . " rows\n";
    } else {
        echo "$table: [Error] " . $conn->error . "\n";
    }
}
?>
