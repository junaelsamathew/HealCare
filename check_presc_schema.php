<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE prescriptions");
if($res) {
    while($row = $res->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
