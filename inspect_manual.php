<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE manual_reports");
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}
?>
