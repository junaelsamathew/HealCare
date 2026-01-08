<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT user_id, email FROM users WHERE user_id IN (11, 19)");
while($row = $res->fetch_assoc()) {
    echo "UID: " . $row['user_id'] . " | EMAIL: [" . $row['email'] . "] | LEN: " . strlen($row['email']) . "\n";
}
?>
