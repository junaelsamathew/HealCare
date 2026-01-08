<?php
include 'includes/db_connect.php';
$uname = 'augustienjoyaljose';
$res = $conn->query("SELECT u.user_id, u.username, u.registration_id, r.name FROM users u LEFT JOIN registrations r ON u.registration_id = r.registration_id WHERE u.username = '$uname' OR u.email LIKE '%$uname%'");
if($row = $res->fetch_assoc()) {
    echo "MATCH FOUND:\n";
    echo "User ID: " . $row['user_id'] . "\n";
    echo "Username: " . $row['username'] . "\n";
    echo "Reg Name: " . $row['name'] . "\n";
} else {
    echo "NO MATCH FOR $uname\n";
    // List all users to see what we have
    $res2 = $conn->query("SELECT username, email FROM users");
    while($r2 = $res2->fetch_assoc()) {
        echo "User: " . $r2['username'] . " (" . $r2['email'] . ")\n";
    }
}
?>
