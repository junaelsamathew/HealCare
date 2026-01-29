<?php
$conn = new mysqli("127.0.0.1", "root", "", "HealCare", 3306);
$res = $conn->query("DESCRIBE users");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}
?>
