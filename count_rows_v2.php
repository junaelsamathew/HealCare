<?php
include 'includes/db_connect.php';

$tables = ['users', 'registrations', 'patient_profiles', 'appointments', 'doctors', 'billing', 'lab_tests', 'canteen_menu', 'ambulance_contacts'];

foreach ($tables as $table) {
    try {
        $res = $conn->query("SELECT COUNT(*) as count FROM $table");
        if ($res) {
            $row = $res->fetch_assoc();
            echo "$table: " . $row['count'] . " rows\n";
        } else {
            echo "$table: [Manual Error] " . $conn->error . "\n";
        }
    } catch (Exception $e) {
        echo "$table: [Exception] " . $e->getMessage() . "\n";
    }
}
?>
