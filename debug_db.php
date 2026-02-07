<?php
include 'includes/db_connect.php';

// Check current count
$res = $conn->query("SELECT COUNT(*) as count FROM canteen_menu");
echo "Count before: " . $res->fetch_assoc()['count'] . "\n";

$items = [
    ['Test Item', 'Lunch', 10, 'Available', 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400']
];

$stmt = $conn->prepare("INSERT INTO canteen_menu (item_name, item_category, price, availability, image_url) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssis s", $items[0][0], $items[0][1], $items[0][2], $items[0][3], $items[0][4]);
$stmt->execute();

$res = $conn->query("SELECT COUNT(*) as count FROM canteen_menu");
echo "Count after: " . $res->fetch_assoc()['count'] . "\n";
?>
