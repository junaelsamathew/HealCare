<?php
$conn = new mysqli("127.0.0.1", "root", "", "healcare");
$res = $conn->query("SELECT user_role, COUNT(*) as count FROM users GROUP BY user_role");
while($row = $res->fetch_assoc()) {
    echo $row['user_role'] . ": " . $row['count'] . "\n";
}
?>
