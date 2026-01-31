<?php
include 'includes/db_connect.php';
$result = $conn->query("DESCRIBE prescriptions");
while($row = $result->fetch_assoc()){
    print_r($row);
}
?>
