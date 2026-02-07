<?php
session_start();
include 'includes/db_connect.php';

echo "Session Role: " . ($_SESSION['user_role'] ?? 'Not set') . "<br>";
echo "Session User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";

$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id > 0) {
    $c = $conn->query("SELECT 'nurse' as t FROM nurses WHERE user_id=$user_id 
                      UNION SELECT 'lab_staff' FROM lab_staff WHERE user_id=$user_id 
                      UNION SELECT 'pharmacist' FROM pharmacists WHERE user_id=$user_id 
                      UNION SELECT 'receptionist' FROM receptionists WHERE user_id=$user_id 
                      UNION SELECT 'canteen_staff' FROM canteen_staff WHERE user_id=$user_id");
    if($c) {
        while($r = $c->fetch_assoc()) {
            echo "Found Staff Type: " . $r['t'] . "<br>";
        }
    } else {
        echo "Query failed: " . $conn->error . "<br>";
    }
}
?>
