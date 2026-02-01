<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT u.user_id, u.username, u.email, u.registration_id, r.name FROM users u LEFT JOIN registrations r ON u.registration_id = r.registration_id");
echo "<table border='1'><tr><th>User ID</th><th>Username</th><th>Email</th><th>Reg ID</th><th>Name from Reg</th></tr>";
while($row = $res->fetch_assoc()) {
    echo "<tr><td>".$row['user_id']."</td><td>".$row['username']."</td><td>".$row['email']."</td><td>".$row['registration_id']."</td><td>".$row['name']."</td></tr>";
}
echo "</table>";
?>
