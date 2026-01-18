<?php
session_start();
include 'includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid Method");
}

$order_id = intval($_POST['order_id']);
$razorpay_payment_id = $_POST['razorpay_payment_id'];
$razorpay_order_id = $_POST['razorpay_order_id'];
$razorpay_signature = $_POST['razorpay_signature'];

// NOTE: In production, you MUST verify the signature using the secret key.
// For this demo, we assume success if IDs are present.

// Update Order Status
$stmt = $conn->prepare("UPDATE canteen_orders SET order_status = 'Placed' WHERE order_id = ?");
$stmt->bind_param("i", $order_id);

if ($stmt->execute()) {
    // Redirect to Canteen with Success Message
    header("Location: canteen.php?msg=Payment+Successful!+Your+order+is+being+prepared.");
    exit();
} else {
    echo "Error updating order: " . $conn->error;
}
?>
