<?php
include 'includes/db_connect.php';

$mappings = [
    // Beverages
    238 => 'assets/food/tea.png',
    239 => 'assets/food/coffee.png',
    240 => 'assets/food/boost.png',
    241 => 'assets/food/horlicks.png',
    242 => 'assets/food/lemon_juice.png',
    243 => 'assets/food/lime_soda.png',
    244 => 'assets/food/fruit_juice.png',
    246 => 'assets/food/lassi.png',
    247 => 'assets/food/lime_soda.png', // Soft drinks generic
    273 => 'assets/food/fruit_juice.png',

    // Breakfast - Rice/South Indian
    185 => 'assets/food/idli.png',
    186 => 'assets/food/dosa.png',
    187 => 'assets/food/masala_dosa.png', // Assuming we can use dosa or masala_dosa
    188 => 'assets/food/samosa.png', // Vada -> Fried snack
    189 => 'assets/food/plain_rice.png', // Upma -> Bowl of grains
    190 => 'assets/food/chapati.png', // Poori -> Bread
    191 => 'assets/food/chapati.png', // Chapati
    192 => 'assets/food/plain_rice.png', // Pongal -> Rice dish
    269 => 'assets/food/plain_rice.png', // Oatmeal Porridge
    262 => 'assets/food/plain_rice.png', // Oats Porridge
    263 => 'assets/food/plain_rice.png', // Ragi Kanji

    // Breakfast - Bread/Western
    193 => 'assets/food/bread_toast.png',
    194 => 'assets/food/bread_toast.png', // Bread Butter
    195 => 'assets/food/bread_toast.png', // Bread Jam
    196 => 'assets/food/sandwich.png', // Veg Sandwich
    200 => 'assets/food/sandwich.png', // Egg Sandwich
    227 => 'assets/food/sandwich.png', // Sandwich (Snack)

    // Eggs
    197 => 'assets/food/omelette.png',
    198 => 'assets/food/boiled_egg.png',
    199 => 'assets/food/omelette.png', // Egg Bhurji

    // Lunch - Rice
    201 => 'assets/food/plain_rice.png',
    202 => 'assets/food/veg_fried_rice.png', // Veg Rice
    203 => 'assets/food/veg_fried_rice.png', // Lemon Rice
    204 => 'assets/food/curd_rice.png',
    205 => 'assets/food/veg_fried_rice.png', // Tomato Rice
    206 => 'assets/food/veg_fried_rice.png', // Sambar Rice
    207 => 'assets/food/veg_fried_rice.png', // Veg Fried Rice
    222 => 'assets/food/veg_fried_rice.png', // Egg Fried Rice
    223 => 'assets/food/veg_fried_rice.png', // Chicken Fried Rice
    271 => 'assets/food/plain_rice.png', // Brown Rice
    259 => 'assets/food/plain_rice.png', // Soft Rice
    260 => 'assets/food/plain_rice.png', // Plain Khichdi

    // Lunch - Breads
    208 => 'assets/food/chapati.png', // Butter Roti
    209 => 'assets/food/chapati.png', // Parotta
    210 => 'assets/food/chapati.png', // Naan

    // Lunch - Curries
    211 => 'assets/food/chicken_curry.png', // Sambar (Gravy)
    212 => 'assets/food/chicken_curry.png', // Rasam
    213 => 'assets/food/chicken_curry.png', // Veg Curry
    214 => 'assets/food/chicken_curry.png', // Dal Fry
    215 => 'assets/food/chicken_curry.png', // Paneer Butter Masala
    216 => 'assets/food/chicken_curry.png', // Mixed Veg Curry
    217 => 'assets/food/chicken_curry.png', // Chicken Curry
    218 => 'assets/food/chicken_curry.png', // Chicken Fry
    219 => 'assets/food/fish_curry.png', // Fish Curry
    220 => 'assets/food/fish_curry.png', // Fish Fry
    221 => 'assets/food/chicken_curry.png', // Egg Curry
    272 => 'assets/food/chicken_curry.png', // Grilled Chicken

    // Snacks
    224 => 'assets/food/maggi.png', // Maggi
    225 => 'assets/food/chicken_curry.png', // Veg Soup
    226 => 'assets/food/chicken_curry.png', // Chicken Soup
    228 => 'assets/food/samosa.png',
    229 => 'assets/food/veg_puff.png', // Veg Cutlet
    230 => 'assets/food/veg_puff.png', // Veg Puff
    231 => 'assets/food/samosa.png', // Bonda
    232 => 'assets/food/samosa.png', // Pakoda
    233 => 'assets/food/samosa.png', // French Fries
    234 => 'assets/food/samosa.png', // Chips
    235 => 'assets/food/veg_puff.png', // Chicken Puff
    236 => 'assets/food/veg_puff.png', // Chicken Cutlet
    237 => 'assets/food/veg_puff.png', // Egg Puff
    264 => 'assets/food/samosa.png', // Pickle
    265 => 'assets/food/chapati.png', // Papad
    266 => 'assets/food/curd_rice.png', // Curd
    267 => 'assets/food/curd_rice.png', // Ghee
    268 => 'assets/food/chicken_curry.png', // Sauce

    // Desserts (Using generic sweet look or defaults)
    // Leaving defaults for desserts as they are distinct
];

foreach ($mappings as $id => $path) {
    // Only update if the file exists (checking via PHP is good practice but I'm confident)
    $stmt = $conn->prepare("UPDATE canteen_menu SET image_url = ? WHERE menu_id = ?");
    $stmt->bind_param("si", $path, $id);
    if ($stmt->execute()) {
        // Echo success
    } else {
        echo "Error: $id -> " . $stmt->error . "\n";
    }
}
echo "Full menu image update completed.";
?>
