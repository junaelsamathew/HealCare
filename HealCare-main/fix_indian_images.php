<?php
include 'includes/db_connect.php';

$image_map = [
    // Breakfast
    'Idli' => 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=400',
    'Dosa' => 'https://images.unsplash.com/photo-1610192244261-3f3363955815?w=400',
    'Masala Dosa' => 'https://images.unsplash.com/photo-1610192244261-3f3363955815?w=400',
    'Vada' => 'https://images.unsplash.com/photo-1626132646529-5003c40787a7?w=400',
    'Upma' => 'https://images.unsplash.com/photo-1614332287897-cdc485fa562d?w=400',
    'Poori' => 'https://images.unsplash.com/photo-1627308595229-7830a5c91f9f?w=400',
    'Chapati' => 'https://images.unsplash.com/photo-1626074353765-517a681e40be?w=400',
    'Pongal' => 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=400',
    'Bread Toast' => 'https://images.unsplash.com/photo-1525351484163-7529414344d8?w=400',
    'Bread Butter' => 'https://images.unsplash.com/photo-1593001874117-c99c5edbb862?w=400',
    'Bread Jam' => 'https://images.unsplash.com/photo-1594627044644-f49c73045d1b?w=400',
    'Vegetable Sandwich' => 'https://images.unsplash.com/photo-1521390188846-e2a3ef18035b?w=400',
    'Egg Omelette' => 'https://images.unsplash.com/photo-1510693206972-df098062cb71?w=400',
    'Boiled Egg' => 'https://images.unsplash.com/photo-1587486913049-53fc88980cfc?w=400',
    'Egg Bhurji' => 'https://images.unsplash.com/photo-1626776878891-628dcd98114f?w=400',
    'Egg Sandwich' => 'https://images.unsplash.com/photo-1525351484163-7529414344d8?w=400',

    // Lunch
    'Plain Rice' => 'https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400',
    'Vegetable Rice' => 'https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400',
    'Lemon Rice' => 'https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400',
    'Curd Rice' => 'https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400',
    'Tomato Rice' => 'https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400',
    'Sambar Rice' => 'https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400',
    'Veg Fried Rice' => 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400',
    'Butter Roti' => 'https://images.unsplash.com/photo-1616070829624-88405a400cc3?w=400',
    'Parotta' => 'https://images.unsplash.com/photo-1616070829624-88405a400cc3?w=400',
    'Naan' => 'https://images.unsplash.com/photo-1533777857889-4be7c70b33f7?w=400',
    'Sambar' => 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=400',
    'Rasam' => 'https://images.unsplash.com/photo-1546830154-8e1d72373059?w=400',
    'Vegetable Curry' => 'https://images.unsplash.com/photo-1588675646184-f5b0b0b0b2de?w=400',
    'Dal Fry' => 'https://images.unsplash.com/photo-1546830154-8e1d72373059?w=400',
    'Paneer Butter Masala' => 'https://images.unsplash.com/photo-1631452180519-c014fe946bc7?w=400',
    'Mixed Veg Curry' => 'https://images.unsplash.com/photo-1588675646184-f5b0b0b0b2de?w=400',
    'Chicken Curry' => 'https://images.unsplash.com/photo-1603894584115-f73f2ec851ad?w=400',
    'Chicken Fry' => 'https://images.unsplash.com/photo-1562967914-608f82629710?w=400',
    'Fish Curry' => 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=400',
    'Fish Fry' => 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=400',
    'Egg Curry' => 'https://images.unsplash.com/photo-1510693206972-df098062cb71?w=400',

    // Dinner 
    'Egg Fried Rice' => 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400',
    'Chicken Fried Rice' => 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400',
    'Maggi' => 'https://images.unsplash.com/photo-1612929633738-8fe44f7ec841?w=400',
    'Vegetable Soup' => 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=400',
    'Chicken Soup' => 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=400',
    'Sandwich' => 'https://images.unsplash.com/photo-1521390188846-e2a3ef18035b?w=400',
    'Samosa' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400',
    'Veg Cutlet' => 'https://images.unsplash.com/photo-1599487488170-eb11f736b84a?w=400',
    'Veg Puff' => 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=400',
    'Bonda' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400',
    'Pakoda' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400',
    'French Fries' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=400',
    'Chips' => 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?w=400',
    'Chicken Puff' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400',
    'Chicken Cutlet' => 'https://images.unsplash.com/photo-1599487488170-eb11f736b84a?w=400',
    'Egg Puff' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400',

    // Beverages
    'Tea' => 'https://images.unsplash.com/photo-1544787210-2211d7c928c7?w=400',
    'Coffee' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400',
    'Boost' => 'https://images.unsplash.com/photo-1544787210-2211d7c928c7?w=400',
    'Horlicks' => 'https://images.unsplash.com/photo-1544787210-2211d7c928c7?w=400',
    'Lemon Juice' => 'https://images.unsplash.com/photo-1534353436294-0dbd4bdac845?w=400',
    'Lime Soda' => 'https://images.unsplash.com/photo-1534351336040-cf00e84b8408?w=400',
    'Fresh Fruit Juice' => 'https://images.unsplash.com/photo-1612948616148-73236e8b4e85?w=400',
    'Buttermilk' => 'https://images.unsplash.com/photo-1600718374662-0483d2b9d40d?w=400',
    'Lassi' => 'https://images.unsplash.com/photo-1550586678-f7225f03c44b?w=400',
    'Soft Drinks' => 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=400',

    // Desserts
    'Gulab Jamun' => 'https://images.unsplash.com/photo-1614332287897-cdc485fa562d?w=400',
    'Jalebi' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400',
    'Laddu' => 'https://images.unsplash.com/photo-1605333396915-47ed6b68a00e?w=400',
    'Ice Cream' => 'https://images.unsplash.com/photo-1497034825429-c343d7c6a68f?w=400',
    'Fruit Salad' => 'https://images.unsplash.com/photo-1490818387583-1baba5e638af?w=400',
    'Payasam' => 'https://images.unsplash.com/photo-1548489115-46a033c467a7?w=400',
    'Apple' => 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=400',
    'Banana' => 'https://images.unsplash.com/photo-1571771894821-ad996211fdf4?w=400',
    'Orange' => 'https://images.unsplash.com/photo-1582979512210-99b6a53386f9?w=400',
    'Papaya' => 'https://images.unsplash.com/photo-1517282004299-31405021e16f?w=400',
    'Watermelon' => 'https://images.unsplash.com/photo-1589927986089-35812388d1f4?w=400',

    // Snacks (Others)
    'Pickle' => 'https://images.unsplash.com/photo-1589218436045-ee320077cd04?w=400',
    'Papad' => 'https://images.unsplash.com/photo-1626082896492-766af4eb6501?w=400',
    'Curd' => 'https://images.unsplash.com/photo-1485921325833-c519f76c4927?w=400',
    'Ghee' => 'https://images.unsplash.com/photo-1590138541999-923cb95c898b?w=400',
    'Sauce' => 'https://images.unsplash.com/photo-1512152272829-e3139592d56f?w=400',

    // Patient Special
    'Soft Rice' => 'https://images.unsplash.com/photo-1512058560366-cd2427ff6671?w=400',
    'Plain Khichdi' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400',
    'Steamed Vegetables' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400',
    'Oats Porridge' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400',
    'Ragi Kanji' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400'
];

$stmt = $conn->prepare("UPDATE canteen_menu SET image_url = ? WHERE item_name = ?");

foreach ($image_map as $name => $url) {
    $stmt->bind_param("ss", $url, $name);
    $stmt->execute();
}

$conn->query("UPDATE canteen_menu SET image_url = 'https://images.unsplash.com/photo-1610192244261-3f3363955815?w=400' WHERE item_name LIKE '%Dosa%'");
$conn->query("UPDATE canteen_menu SET image_url = 'https://images.unsplash.com/photo-1626132646529-5003c40787a7?w=400' WHERE item_name LIKE '%Vada%'");
$conn->query("UPDATE canteen_menu SET image_url = 'https://images.unsplash.com/photo-1605333396915-47ed6b68a00e?w=400' WHERE item_name LIKE '%Laddu%' OR item_name LIKE '%Ladoo%'");
$conn->query("UPDATE canteen_menu SET image_url = 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=400' WHERE item_name LIKE '%Idli%'");
$conn->query("UPDATE canteen_menu SET image_url = 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400' WHERE item_name LIKE '%Samosa%'");

echo "All images updated with specific Indian food visuals!";
?>
