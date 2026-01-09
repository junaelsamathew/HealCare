<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch cart items
$cart_res = $conn->query("SELECT * FROM canteen_cart WHERE user_id = $user_id");

if ($cart_res && $cart_res->num_rows > 0) {
    $conn->begin_transaction();
    try {
        $location = "Ward B / Bed 15"; // Default
        
        while ($item = $cart_res->fetch_assoc()) {
            $menu_id = $item['menu_id'];
            
            // Get price
            $m_res = $conn->query("SELECT price FROM canteen_menu WHERE menu_id = $menu_id");
            $price = $m_res->fetch_assoc()['price'];
            
            $stmt = $conn->prepare("INSERT INTO canteen_orders (patient_id, menu_id, quantity, order_date, order_time, order_status, total_amount, delivery_location) VALUES (?, ?, 1, CURDATE(), CURTIME(), 'Placed', ?, ?)");
            $stmt->bind_param("iids", $user_id, $menu_id, $price, $location);
            $stmt->execute();
        }
        
        // Clear cart
        $conn->query("DELETE FROM canteen_cart WHERE user_id = $user_id");
        
        $conn->commit();
        header("Location: canteen.php?msg=All+cart+items+have+been+ordered!");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: cart.php?error=Fail+to+place+order");
    }
} else {
    header("Location: canteen.php");
}
exit();
?>
