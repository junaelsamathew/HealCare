<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT u.user_id, r.name FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE r.name LIKE '%Juna%'");
while($row = $res->fetch_assoc()) {
    echo "User ID: " . $row['user_id'] . " - " . $row['name'] . "\n";
    $pid = $row['user_id'];
    echo "--- Lab Tests ---\n";
    $lab = $conn->query("SELECT labtest_id, test_name, status, report_path FROM lab_tests WHERE patient_id = $pid");
    while($l = $lab->fetch_assoc()) {
        print_r($l);
    }
    echo "--- Manual Reports ---\n";
    $man = $conn->query("SELECT * FROM manual_reports WHERE patient_id = $pid");
    while($m = $man->fetch_assoc()) {
        print_r($m);
    }
}
?>
