<?php
include 'includes/db_connect.php';

// First, delete everything from the canteen_menu table
$conn->query("DELETE FROM canteen_menu");

// Comprehensive list from the user's request
$items = [
    // Breakfast
    ['Idli', 'Breakfast', 30, 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=300'],
    ['Dosa', 'Breakfast', 40, 'https://images.unsplash.com/photo-1589302168068-964664d93dc0?w=300'],
    ['Masala Dosa', 'Breakfast', 50, 'https://images.unsplash.com/photo-1630406184470-7fd4440ef826?w=300'],
    ['Vada', 'Breakfast', 20, 'https://images.unsplash.com/photo-1626132646529-5003c40787a7?w=300'],
    ['Upma', 'Breakfast', 40, 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=300'],
    ['Poori', 'Breakfast', 50, 'https://images.unsplash.com/photo-1627308595229-7830a5c91f9f?w=300'],
    ['Chapati', 'Breakfast', 20, 'https://images.unsplash.com/photo-1626074353765-517a681e40be?w=300'],
    ['Pongal', 'Breakfast', 45, 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=300'],
    ['Bread Toast', 'Breakfast', 30, 'https://images.unsplash.com/photo-1525351484163-7529414344d8?w=300'],
    ['Bread Butter', 'Breakfast', 35, 'https://images.unsplash.com/photo-1593001874117-c99c5edbb862?w=300'],
    ['Bread Jam', 'Breakfast', 35, 'https://images.unsplash.com/photo-1594627044644-f49c73045d1b?w=400'],
    ['Vegetable Sandwich', 'Breakfast', 60, 'https://images.unsplash.com/photo-1521390188846-e2a3ef18035b?w=400'],
    ['Egg Omelette', 'Breakfast', 40, 'https://images.unsplash.com/photo-1510693206972-df098062cb71?w=400'],
    ['Boiled Egg', 'Breakfast', 15, 'https://images.unsplash.com/photo-1525351484163-7529414344d8?w=300'],
    ['Egg Bhurji', 'Breakfast', 50, 'https://images.unsplash.com/photo-1626776878891-628dcd98114f?w=400'],
    ['Egg Sandwich', 'Breakfast', 70, 'https://images.unsplash.com/photo-1525351484163-7529414344d8?w=300'],

    // Lunch
    ['Plain Rice', 'Lunch', 30, 'https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400'],
    ['Vegetable Rice', 'Lunch', 80, 'https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400'],
    ['Lemon Rice', 'Lunch', 60, 'https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400'],
    ['Curd Rice', 'Lunch', 50, 'https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400'],
    ['Tomato Rice', 'Lunch', 60, 'https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400'],
    ['Sambar Rice', 'Lunch', 65, 'https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400'],
    ['Veg Fried Rice', 'Lunch', 100, 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400'],
    ['Butter Roti', 'Lunch', 20, 'https://images.unsplash.com/photo-1626074353765-517a681e40be?w=400'],
    ['Parotta', 'Lunch', 25, 'https://images.unsplash.com/photo-1616070829624-88405a400cc3?w=400'],
    ['Naan', 'Lunch', 40, 'https://images.unsplash.com/photo-1533777857889-4be7c70b33f7?w=400'],
    ['Sambar', 'Lunch', 40, 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=400'],
    ['Rasam', 'Lunch', 30, 'https://images.unsplash.com/photo-1546830154-8e1d72373059?w=400'],
    ['Vegetable Curry', 'Lunch', 60, 'https://images.unsplash.com/photo-1588675646184-f5b0b0b0b2de?w=400'],
    ['Dal Fry', 'Lunch', 70, 'https://images.unsplash.com/photo-1546830154-8e1d72373059?w=400'],
    ['Paneer Butter Masala', 'Lunch', 150, 'https://images.unsplash.com/photo-1631452180519-c014fe946bc7?w=400'],
    ['Mixed Veg Curry', 'Lunch', 80, 'https://images.unsplash.com/photo-1588675646184-f5b0b0b0b2de?w=400'],
    ['Chicken Curry', 'Lunch', 180, 'https://images.unsplash.com/photo-1603894584115-f73f2ec851ad?w=400'],
    ['Chicken Fry', 'Lunch', 160, 'https://images.unsplash.com/photo-1562967914-608f82629710?w=400'],
    ['Fish Curry', 'Lunch', 200, 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=400'],
    ['Fish Fry', 'Lunch', 180, 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=400'],
    ['Egg Curry', 'Lunch', 90, 'https://images.unsplash.com/photo-1510693206972-df098062cb71?w=400'],

    // Dinner
    ['Egg Fried Rice', 'Dinner', 120, 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400'],
    ['Chicken Fried Rice', 'Dinner', 150, 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400'],
    ['Maggi', 'Dinner', 50, 'https://images.unsplash.com/photo-1612929633738-8fe44f7ec841?w=400'],

    // Snacks
    ['Vegetable Soup', 'Snacks', 60, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=400'],
    ['Chicken Soup', 'Snacks', 90, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=400'],
    ['Sandwich', 'Snacks', 70, 'https://images.unsplash.com/photo-1521390188846-e2a3ef18035b?w=400'],
    ['Samosa', 'Snacks', 15, 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400'],
    ['Veg Cutlet', 'Snacks', 40, 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400'],
    ['Veg Puff', 'Snacks', 25, 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400'],
    ['Bonda', 'Snacks', 15, 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400'],
    ['Pakoda', 'Snacks', 30, 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400'],
    ['French Fries', 'Snacks', 90, 'https://images.unsplash.com/photo-1630384066202-1777b760b11d?w=400'],
    ['Chips', 'Snacks', 30, 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?w=400'],
    ['Chicken Puff', 'Snacks', 40, 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400'],
    ['Chicken Cutlet', 'Snacks', 60, 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400'],
    ['Egg Puff', 'Snacks', 30, 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400'],

    // Beverages
    ['Tea', 'Beverages', 15, 'https://images.unsplash.com/photo-1544787210-2211d7c928c7?w=400'],
    ['Coffee', 'Beverages', 20, 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400'],
    ['Boost', 'Beverages', 40, 'https://images.unsplash.com/photo-1544787210-2211d7c928c7?w=400'],
    ['Horlicks', 'Beverages', 40, 'https://images.unsplash.com/photo-1544787210-2211d7c928c7?w=400'],
    ['Lemon Juice', 'Beverages', 30, 'https://images.unsplash.com/photo-1513558161293-cdaf76c2016b?w=400'],
    ['Lime Soda', 'Beverages', 40, 'https://images.unsplash.com/photo-1513558161293-cdaf76c2016b?w=400'],
    ['Fresh Fruit Juice', 'Beverages', 60, 'https://images.unsplash.com/photo-1513558161293-cdaf76c2016b?w=400'],
    ['Buttermilk', 'Beverages', 25, 'https://images.unsplash.com/photo-1513558161293-cdaf76c2016b?w=400'],
    ['Lassi', 'Beverages', 50, 'https://images.unsplash.com/photo-1513558161293-cdaf76c2016b?w=400'],
    ['Soft Drinks', 'Beverages', 40, 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=400'],

    // Desserts
    ['Gulab Jamun', 'Desserts', 50, 'https://images.unsplash.com/photo-1548489115-46a033c467a7?w=400'],
    ['Jalebi', 'Desserts', 60, 'https://images.unsplash.com/photo-1548489115-46a033c467a7?w=400'],
    ['Laddu', 'Desserts', 40, 'https://images.unsplash.com/photo-1548489115-46a033c467a7?w=400'],
    ['Ice Cream', 'Desserts', 80, 'https://images.unsplash.com/photo-1497034825429-c343d7c6a68f?w=400'],
    ['Fruit Salad', 'Desserts', 100, 'https://images.unsplash.com/photo-1490818387583-1baba5e638af?w=400'],
    ['Payasam', 'Desserts', 70, 'https://images.unsplash.com/photo-1548489115-46a033c467a7?w=400'],
    ['Apple', 'Desserts', 30, 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=400'],
    ['Banana', 'Desserts', 5, 'https://images.unsplash.com/photo-1571771894821-ad996211fdf4?w=400'],
    ['Orange', 'Desserts', 20, 'https://images.unsplash.com/photo-1582979512210-99b6a53386f9?w=400'],
    ['Papaya', 'Desserts', 40, 'https://images.unsplash.com/photo-1517282004299-31405021e16f?w=400'],
    ['Watermelon', 'Desserts', 50, 'https://images.unsplash.com/photo-1589927986089-35812388d1f4?w=400'],

    // Patient Special
    ['Soft Rice', 'Patient Special', 40, 'https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400'],
    ['Plain Khichdi', 'Patient Special', 60, 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400'],
    ['Steamed Vegetables', 'Patient Special', 70, 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400'],
    ['Oats Porridge', 'Patient Special', 80, 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400'],
    ['Ragi Kanji', 'Patient Special', 50, 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400'],
    
    // Additional requested items (duplicates or variations)
    ['Pickle', 'Snacks', 5, 'https://images.unsplash.com/photo-1589135339689-19aa8bc4ed15?w=400'],
    ['Papad', 'Snacks', 10, 'https://images.unsplash.com/photo-1589135339689-19aa8bc4ed15?w=400'],
    ['Curd', 'Snacks', 20, 'https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400'],
    ['Ghee', 'Snacks', 30, 'https://images.unsplash.com/photo-1589135339689-19aa8bc4ed15?w=400'],
    ['Sauce', 'Snacks', 5, 'https://images.unsplash.com/photo-1589135339689-19aa8bc4ed15?w=400']
];

$stmt = $conn->prepare("INSERT INTO canteen_menu (item_name, item_category, price, availability, image_url) VALUES (?, ?, ?, 'Available', ?)");

$count = 0;
foreach ($items as $item) {
    $stmt->bind_param("ssis", $item[0], $item[1], $item[2], $item[3]);
    if ($stmt->execute()) {
        $count++;
    }
}

echo "SUCCESS: Inserted $count items into canteen_menu.";
?>
