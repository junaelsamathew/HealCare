<?php
include 'includes/db_connect.php';

echo "--- Lab Staff ---\n";
$res1 = $conn->query("SELECT user_id, lab_type FROM lab_staff");
if($res1) {
    while($row = $res1->fetch_assoc()) {
        print_r($row);
    }
}

echo "\n--- Lab Tests (Distinct Types) ---\n";
$res2 = $conn->query("SELECT DISTINCT test_type FROM lab_tests");
if($res2) {
    while($row = $res2->fetch_assoc()) {
        print_r($row);
    }
}
?>
