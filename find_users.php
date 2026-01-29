<?php
include 'includes/db_connect.php';

$users = ['Ciya John', 'Gigi Tony'];
foreach($users as $name) {
    echo "--- Search: $name ---\n";
    $stmt = $conn->prepare("SELECT u.user_id, u.username, u.user_role, r.name, r.registration_id FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE r.name LIKE ?");
    $search = "%$name%";
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) {
        print_r($row);
        $uid = $row['user_id'];
        
        // Check lab_staff
        $l = $conn->query("SELECT * FROM lab_staff WHERE user_id = $uid");
        if($l->num_rows > 0) { echo "Found in lab_staff: "; print_r($l->fetch_assoc()); }
        
        // Check nurses
        $n = $conn->query("SELECT * FROM nurses WHERE user_id = $uid");
        if($n->num_rows > 0) { echo "Found in nurses: "; print_r($n->fetch_assoc()); }
        
        // Check pharmacists
        $p = $conn->query("SELECT * FROM pharmacists WHERE user_id = $uid");
        if($p->num_rows > 0) { echo "Found in pharmacists: "; print_r($p->fetch_assoc()); }
    }
}
?>
