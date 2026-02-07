<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCIBE canteen_menu"); // Wait, spelled DESCRIBE wrong in my head, let me fix
$res = $conn->query("DESCRIBE canteen_menu");
if ($res) {
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
}
?>
