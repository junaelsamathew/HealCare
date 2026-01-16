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
        
        $total_order_amount = 0;
        $order_items = [];

        // 1. Calculate Total & Prepare Items
        while ($item = $cart_res->fetch_assoc()) {
            $menu_id = $item['menu_id'];
            $m_res = $conn->query("SELECT price FROM canteen_menu WHERE menu_id = $menu_id");
            $price = $m_res->fetch_assoc()['price'];
            $total_order_amount += $price;
            $order_items[] = ['menu_id' => $menu_id, 'price' => $price];
        }

        // 2. Create Bill first
        $bill_date = date('Y-m-d');
        $stmt_bill = $conn->prepare("INSERT INTO billing (patient_id, bill_type, total_amount, payment_status, bill_date) VALUES (?, 'Canteen', ?, 'Pending', ?)");
        $stmt_bill->bind_param("ids", $user_id, $total_order_amount, $bill_date);
        $stmt_bill->execute();
        $bill_id = $conn->insert_id;

        // 3. Insert Orders
        foreach ($order_items as $order) {
            $stmt = $conn->prepare("INSERT INTO canteen_orders (patient_id, menu_id, quantity, order_date, order_time, order_status, total_amount, delivery_location) VALUES (?, ?, 1, CURDATE(), CURTIME(), 'Pending Payment', ?, ?)");
            $stmt->bind_param("iids", $user_id, $order['menu_id'], $order['price'], $location);
            $stmt->execute();
        }
        
        // 4. Clear cart
        $conn->query("DELETE FROM canteen_cart WHERE user_id = $user_id");
        
        $conn->commit();
        
        // 5. Redirect to Payment
        header("Location: payment_process.php?bill_id=$bill_id");

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: cart.php?error=Fail+to+place+order");
    }
} else {
    header("Location: canteen.php");
}
exit();
?>
