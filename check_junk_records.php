<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT record_id, diagnosis, treatment_plan, special_notes FROM medical_records");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['record_id'] . "\n";
    echo "Diagnosis: " . $row['diagnosis'] . "\n";
    echo "Treatment: " . $row['treatment_plan'] . "\n";
    echo "Notes: " . $row['special_notes'] . "\n";
    echo "-------------------\n";
}
?>
