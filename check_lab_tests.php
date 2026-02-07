<?php
include 'includes/db_connect.php';

echo "=== LAB_TESTS COLUMNS ===\n";
$result = $conn->query("DESCRIBE lab_tests");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}

echo "\n=== SAMPLE LAB_TESTS DATA ===\n";
$result2 = $conn->query("SELECT labtest_id, test_name, category, category_id, status FROM lab_tests LIMIT 3");
while($row = $result2->fetch_assoc()) {
    print_r($row);
}
?>
