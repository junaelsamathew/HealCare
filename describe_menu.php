<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE canteen_menu");
if ($res) {
    echo "Columns for canteen_menu:\n";
    while($row = $res->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error: " . $conn->error;
}
?>
