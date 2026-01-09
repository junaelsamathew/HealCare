<?php
include 'includes/db_connect.php';

echo "Checking lab_staff table...\n";
$res = $conn->query("SHOW TABLES LIKE 'lab_staff'");
if ($res->num_rows > 0) {
    echo "Table 'lab_staff' exists.\n";
    $res = $conn->query("SELECT * FROM lab_staff");
    if ($res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            print_r($row);
        }
    } else {
        echo "Table 'lab_staff' is empty.\n";
    }
} else {
    echo "Table 'lab_staff' does not exist.\n";
}

echo "\nChecking lab_orders table entries...\n";
$res = $conn->query("SELECT DISTINCT lab_category FROM lab_orders");
if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        echo "Lab Order Category: " . $row['lab_category'] . "\n";
    }
} else {
    echo "No lab orders found.\n";
}
?>
