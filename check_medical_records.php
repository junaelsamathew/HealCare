<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM medical_records ORDER BY created_at DESC LIMIT 5");
echo "Latest Medical Records:\n";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
