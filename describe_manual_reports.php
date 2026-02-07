<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE manual_reports");
$out = "";
while($row = $res->fetch_assoc()) {
    $out .= $row['Field'] . " - " . $row['Type'] . "\n";
}
file_put_contents('manual_reports_structure.txt', $out);
?>
