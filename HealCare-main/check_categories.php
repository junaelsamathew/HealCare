<?php
include 'includes/db_connect.php';

echo "=== LAB_CATEGORIES ===\n";
$result = $conn->query("SELECT * FROM lab_categories");
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row['category_id'] . " - Name: " . $row['category_name'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
