<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT labtest_id, test_name, status, report_path, result FROM lab_tests WHERE status='Completed' ORDER BY labtest_id DESC LIMIT 5");
if ($res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No completed lab tests found.";
}
?>
