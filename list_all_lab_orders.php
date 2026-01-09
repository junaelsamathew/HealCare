<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM lab_orders");
echo "Total Lab Orders: " . $res->num_rows . "\n";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
