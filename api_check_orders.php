<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'staff') {
    echo json_encode(['count' => 0]);
    exit();
}

$res = $conn->query("SELECT COUNT(*) as count FROM canteen_orders WHERE order_status IN ('Placed', 'Preparing')");
$data = $res->fetch_assoc();
echo json_encode(['count' => (int)$data['count']]);
?>
