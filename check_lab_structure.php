<?php
include 'includes/db_connect.php';

echo "=== LAB_TESTS TABLE STRUCTURE ===\n\n";
$result = $conn->query("DESCRIBE lab_tests");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n\n=== LAB_STAFF TABLE STRUCTURE ===\n\n";
$result2 = $conn->query("DESCRIBE lab_staff");
while($row = $result2->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n\n=== LAB_CATEGORIES TABLE ===\n\n";
$result3 = $conn->query("SELECT * FROM lab_categories");
while($row = $result3->fetch_assoc()) {
    print_r($row);
}
?>
