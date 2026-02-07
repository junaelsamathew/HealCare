<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM lab_tests WHERE labtest_id = 49");
echo json_encode($res->fetch_assoc(), JSON_PRETTY_PRINT);
?>
