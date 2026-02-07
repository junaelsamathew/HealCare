<?php
include 'includes/db_connect.php';

$menu_items = [
    ['Breakfast Set', 'Breakfast', 'Oatmeal, fresh fruits, and a choice of tea or coffee.', 120.00, 'Available', 'Light'],
    ['Lunch Menu', 'Lunch', 'Brown rice, grilled chicken/tofu, and steamed vegetables.', 250.00, 'Available', 'Regular'],
    ['Dinner Menu', 'Dinner', 'Vegetable soup with whole-grain bread rolls.', 180.00, 'Available', 'Light'],
    ['Snacks & Beverages', 'Snacks', 'Nut bars, yogurt, and fresh fruit juices.', 80.00, 'Available', 'Snack'],
    ['Patient Combo A', 'Lunch', 'Ward B Special - Liquid Diet', 150.00, 'Available', 'Liquid'],
    ['Oatmeal Porridge', 'Breakfast', 'Special healthy oatmeal', 90.00, 'Available', 'Light'],
    ['Veg Clear Soup', 'Starter', 'Fresh vegetable clear soup', 70.00, 'Available', 'Liquid'],
    ['Paneer Butter Masala', 'Lunch', 'Classic paneer butter masala', 220.00, 'Sold Out', 'Regular']
];

$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("TRUNCATE TABLE canteen_menu");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

$stmt = $conn->prepare("INSERT INTO canteen_menu (item_name, item_category, description, price, availability, diet_type) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($menu_items as $item) {
    $stmt->bind_param("sssdss", $item[0], $item[1], $item[2], $item[3], $item[4], $item[5]);
    $stmt->execute();
}

echo "Canteen menu populated successfully.";
?>
