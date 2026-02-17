<?php
session_start();
include 'includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $user_id = $_SESSION['user_id'];

        if ($_POST['action'] == 'add_to_cart') {
        $menu_id = $_POST['menu_id'];
        $diet_type = $_POST['diet_type'] ?? 'Normal';

        // Check stock availability
        $stock_res = $conn->query("SELECT stock_quantity FROM canteen_menu WHERE menu_id = $menu_id");
        if ($stock_res && $stock_res->num_rows > 0) {
            $stock = $stock_res->fetch_assoc()['stock_quantity'];
            
            // Check how many are already in cart
            $cart_check = $conn->query("SELECT COUNT(*) as count FROM canteen_cart WHERE user_id = $user_id AND menu_id = $menu_id");
            $in_cart = $cart_check->fetch_assoc()['count'];

            if ($in_cart >= $stock) {
                echo json_encode(['status' => 'error', 'message' => 'Out of Stock!']);
                exit();
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Item not found']);
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO canteen_cart (user_id, menu_id, diet_type) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $menu_id, $diet_type);

        if ($stmt->execute()) {
            // Get current cart count
            $count_res = $conn->query("SELECT COUNT(*) as count FROM canteen_cart WHERE user_id = $user_id");
            $count = $count_res->fetch_assoc()['count'];
            echo json_encode(['status' => 'success', 'count' => $count]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    }
}
?>
