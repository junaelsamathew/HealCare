<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE users");
$out = "";
while($row = $res->fetch_assoc()) {
    $out .= $row['Field'] . " - " . $row['Type'] . "\n";
}
file_put_contents('users_structure.txt', $out);
?>
