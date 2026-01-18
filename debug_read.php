<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT bill_id, reference_id, total_amount, bill_type FROM billing ORDER BY bill_id DESC LIMIT 10");
echo "<pre>";
echo "BillID | RefID | Amount | Type\n";
echo "-------------------------------\n";
while($row = $res->fetch_assoc()) {
    echo $row['bill_id'] . " | " . $row['reference_id'] . " | " . $row['total_amount'] . " | " . $row['bill_type'] . "\n";
}
echo "</pre>";
?>
