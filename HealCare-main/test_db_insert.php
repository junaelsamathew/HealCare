<?php
include 'includes/db_connect.php';
echo "Testing Insert...\n";
$stmt = $conn->prepare("INSERT INTO billing (patient_id, doctor_id, appointment_id, reference_id, bill_type, total_amount, payment_status, bill_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if(!$stmt) die("Prepare failed: ".$conn->error);

$pid = 1; 
$did = 1; 
$aid = null; 
$ref = 999; 
$type='Pharmacy'; 
$amt=150.00; 
$stat='Pending'; 
$date='2025-01-01';

$stmt->bind_param("iiiisdss", $pid, $did, $aid, $ref, $type, $amt, $stat, $date);

if($stmt->execute()) {
    echo "Success! Inserted Bill ID: " . $conn->insert_id . "\n";
} else {
    echo "Error executing stmt: " . $stmt->error . "\n";
}
?>
