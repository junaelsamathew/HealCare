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
