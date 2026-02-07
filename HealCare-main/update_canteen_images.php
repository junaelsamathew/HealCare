<?php
include 'includes/db_connect.php';

$updates = [
    240 => 'assets/food/boost.png',
    241 => 'assets/food/horlicks.png',
    242 => 'assets/food/lemon_juice.png',
    243 => 'assets/food/lime_soda.png',
    244 => 'assets/food/fruit_juice.png',
    246 => 'assets/food/lassi.png',
    273 => 'assets/food/fruit_juice.png'
];

foreach ($updates as $id => $path) {
    $stmt = $conn->prepare("UPDATE canteen_menu SET image_url = ? WHERE menu_id = ?");
    $stmt->bind_param("si", $path, $id);
    if ($stmt->execute()) {
        echo "Updated Menu ID $id to $path\n";
    } else {
        echo "Error updating ID $id: " . $stmt->error . "\n";
    }
    $stmt->close();
}
echo "All updates completed.";
?>
