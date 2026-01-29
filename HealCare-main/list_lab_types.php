<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT DISTINCT lab_type FROM lab_staff");
while($row = $res->fetch_assoc()) {
    echo "LAB_TYPE:[" . $row['lab_type'] . "]\n";
}
?>
