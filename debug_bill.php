<?php
include 'includes/db_connect.php';
$res = $conn->query('SELECT b.bill_id, b.bill_type, b.appointment_id, b.payment_status FROM billing b ORDER BY b.bill_id DESC LIMIT 1'); 
print_r($res->fetch_assoc());
?>
