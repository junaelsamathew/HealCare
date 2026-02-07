<?php
session_start();
include 'includes/db_connect.php';

// Only for Test Mode
// In production, remove this file or protect it.

if (!isset($_GET['order_id'])) {
    die("Invalid Request");
}

$order_id = intval($_GET['order_id']);

// Force update to 'Placed' for testing
$stmt = $conn->prepare("UPDATE canteen_orders SET order_status = 'Placed' WHERE order_id = ?");
$stmt->bind_param("i", $order_id);

if ($stmt->execute()) {
    header("Location: canteen.php?msg=Test+Payment+Successful!+Your+order+is+placed.");
    exit();
} else {
    echo "Error: " . $conn->error;
}
?>
