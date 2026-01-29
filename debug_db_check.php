<?php
include 'includes/db_connect.php';

echo "LATEST BILL:\n";
$res = $conn->query("SELECT * FROM billing ORDER BY bill_id DESC LIMIT 1");
$bill = $res->fetch_assoc();
print_r($bill);

echo "\nLATEST APPOINTMENT:\n";
$res2 = $conn->query("SELECT * FROM appointments ORDER BY appointment_id DESC LIMIT 1");
$appt = $res2->fetch_assoc();
print_r($appt);

if ($bill['bill_type'] == 'Consultation') {
    echo "\nBILL TYPE MATCHES 'Consultation'\n";
} else {
    echo "\nBILL TYPE: '" . $bill['bill_type'] . "' (Does NOT match 'Consultation')\n";
}

// Check Patient Email
$pat_id = $appt['patient_id'];
$q = $conn->query("SELECT email, username, permissions FROM users WHERE user_id = $pat_id");
echo "\nPATIENT DATA:\n";
print_r($q->fetch_assoc());

?>
