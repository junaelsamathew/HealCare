<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT r.registration_id, r.name, r.email, r.staff_type, u.user_id, u.role, u.username 
                   FROM registrations r 
                   LEFT JOIN users u ON r.registration_id = u.registration_id 
                   WHERE r.name LIKE '%Mini Jose%'");
while($row = $res->fetch_assoc()) {
    print_r($row);
    $uid = $row['user_id'];
    if($uid) {
        $tables = ['nurses', 'lab_staff', 'pharmacists', 'receptionists', 'canteen_staff'];
        foreach($tables as $t) {
            $check = $conn->query("SELECT * FROM $t WHERE user_id = $uid");
            if($check && $check->num_rows > 0) {
                echo "Found in Table: $t\n";
            }
        }
    }
}
?>
