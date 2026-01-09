<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle removal
if (isset($_GET['remove'])) {
    $cart_id = $_GET['remove'];
    $conn->query("DELETE FROM canteen_cart WHERE cart_id = $cart_id AND user_id = $user_id");
    header("Location: cart.php");
    exit();
}

// Fetch cart items
$cart_items = $conn->query("
    SELECT c.*, m.item_name, m.price, m.description, m.item_category 
    FROM canteen_cart c 
    JOIN canteen_menu m ON c.menu_id = m.menu_id 
    WHERE c.user_id = $user_id
");

$total_cart_amount = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #020617;
            --card-bg: #0f172a;
            --primary-blue: #3b82f6;
            --text-gray: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.05);
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-dark); color: #fff; margin: 0; padding: 40px 5%; line-height: 1.6; }
        
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 40px; }
        h1 { font-size: 32px; font-weight: 700; margin: 0; }
        
        .cart-container { max-width: 800px; margin: 0 auto; }
        
        .cart-item {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: 0.3s;
        }
        .cart-item:hover { border-color: var(--primary-blue); }
        
        .item-icon {
            width: 60px;
            height: 60px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--primary-blue);
        }
        
        .item-details { flex: 1; }
        .item-name { font-size: 18px; font-weight: 700; margin: 0; }
        .item-meta { font-size: 13px; color: var(--text-gray); }
        
        .item-price { font-size: 18px; font-weight: 700; color: #fff; }
        
        .btn-remove {
            color: #ef4444;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            transition: 0.3s;
        }
        .btn-remove:hover { transform: scale(1.2); }
        
        .cart-summary {
            margin-top: 40px;
            padding: 25px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            text-align: right;
        }
        .total-row { font-size: 24px; font-weight: 800; margin-bottom: 20px; }
        .total-label { color: var(--text-gray); margin-right: 20px; font-weight: 500; }
        
        .btn-checkout {
            background: var(--primary-blue);
            color: #fff;
            border: none;
            padding: 15px 40px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-checkout:hover { background: #2563eb; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3); }
        
        .empty-cart { text-align: center; padding: 60px; color: var(--text-gray); }
        .empty-cart i { font-size: 48px; margin-bottom: 20px; display: block; }
    </style>
</head>
<body>

    <div class="cart-container">
        <div class="header">
            <div>
                <a href="canteen.php" style="color: var(--text-gray); text-decoration: none; font-size: 14px; margin-bottom: 10px; display: inline-block;"><i class="fas fa-arrow-left"></i> Back to Menu</a>
                <h1>Your Cart</h1>
            </div>
            <i class="fas fa-shopping-basket" style="font-size: 32px; color: var(--primary-blue); opacity: 0.5;"></i>
        </div>

        <?php if ($cart_items && $cart_items->num_rows > 0): ?>
            <div class="cart-list">
                <?php while ($item = $cart_items->fetch_assoc()): 
                    $total_cart_amount += $item['price'];
                    $icon = "fa-utensils";
                    if ($item['item_category'] == "Morning / Breakfast") $icon = "fa-mug-hot";
                    elseif ($item['item_category'] == "Lunch") $icon = "fa-bowl-rice";
                    elseif ($item['item_category'] == "Evening Snacks") $icon = "fa-cookie";
                    elseif ($item['item_category'] == "Dinner") $icon = "fa-leaf";
                ?>
                    <div class="cart-item">
                        <div class="item-icon"><i class="fas <?php echo $icon; ?>"></i></div>
                        <div class="item-details">
                            <h3 class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></h3>
                            <div class="item-meta">Diet: <?php echo htmlspecialchars($item['diet_type']); ?> | Category: <?php echo htmlspecialchars($item['item_category']); ?></div>
                        </div>
                        <div class="item-price">₹<?php echo number_format($item['price'], 0); ?></div>
                        <a href="cart.php?remove=<?php echo $item['cart_id']; ?>" class="btn-remove" title="Remove Item"><i class="fas fa-trash-alt"></i></a>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="cart-summary">
                <div class="total-row">
                    <span class="total-label">Total Amount:</span>
                    <span>₹<?php echo number_format($total_cart_amount, 0); ?></span>
                </div>
                <a href="place_cart_order.php" class="btn-checkout">Place All Orders</a>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-cart-arrow-down"></i>
                <p>Your cart is empty. Start adding some delicious food!</p>
                <a href="canteen.php" class="btn-checkout" style="margin-top: 20px;">Browse Menu</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
