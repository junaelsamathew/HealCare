<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT doctor_id, appointment_date, COUNT(*) as cnt, MAX(queue_number) as max_q FROM appointments GROUP BY doctor_id, appointment_date");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
