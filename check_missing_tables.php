<?php
include 'includes/db_connect.php';
$res = $conn->query("SHOW TABLES");
$current_tables = [];
while($row = $res->fetch_array()) {
    $current_tables[] = $row[0];
}

$expected_tables = [
    'ambulance_contacts',
    'appointments',
    'billing',
    'canteen_menu',
    'canteen_orders',
    'complaint_logs',
    'doctors',
    'health_packages',
    'lab_tests',
    'medical_records',
    'patient_medical_records',
    'patient_profiles',
    'payments',
    'pharmacy_stock',
    'prescriptions',
    'registrations',
    'reports',
    'users'
];

echo "Current Tables:\n";
foreach($current_tables as $t) echo "- $t\n";

echo "\nMissing Tables:\n";
foreach($expected_tables as $t) {
    if(!in_array($t, $current_tables)) echo "- $t\n";
}
?>
