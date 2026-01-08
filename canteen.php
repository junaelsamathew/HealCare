<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$msg_type = "success";

// 1. ADMITTED PATIENTS CHECK
// We check if the patient has an entry in patient_profiles (standard for all patients)
// For "Admitted", we theoretically check if they have a bed. 
// Since bed management might be in a different table, let's look for bed-related columns.
$is_admitted = false;
$check_admitted = $conn->query("SELECT * FROM patient_profiles WHERE user_id = $user_id");
if ($check_admitted && $check_admitted->num_rows > 0) {
    $is_admitted = true; // For this prototype, all registered patients are eligible to see menu
    // But we will show a specific message if they don't have a profile
} else {
    // If no profile, they might not be fully registered as a patient
    header("Location: patient_dashboard.php?msg=Please+complete+your+profile+to+order+food");
    exit();
}

// Fetch patient's diet preference if exists
$diet_pref = "Normal";
$p_data = $check_admitted->fetch_assoc();
$diet_pref = $p_data['diet_preference'] ?? 'Normal';

// 2. ORDER PLACEMENT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    $menu_id = $_POST['menu_id'];
    $item_name = $_POST['food_item'];
    $price = $_POST['price'];
    $requested_diet = $_POST['requested_diet'] ?? 'Normal';
    
    // Insert with 'Placed' status as requested
    $location = "Ward B / Bed 15"; // Placeholder for admitted location
    $stmt = $conn->prepare("INSERT INTO canteen_orders (patient_id, menu_id, quantity, order_date, order_time, order_status, total_amount, delivery_location) VALUES (?, ?, 1, CURDATE(), CURTIME(), 'Placed', ?, ?)");
    $stmt->bind_param("iids", $user_id, $menu_id, $price, $location);
    
    if ($stmt->execute()) {
        $message = "Your order for $item_name has been placed! It will be delivered to your room shortly.";
        $msg_type = "success";
    } else {
        $message = "Error placing order: " . $conn->error;
        $msg_type = "error";
    }
}

// 3. TIME-BASED AVAILABILITY LOGIC
$current_hour = (int)date('H');
$active_category = "";
if ($current_hour >= 6 && $current_hour < 11) $active_category = "Morning / Breakfast";
elseif ($current_hour >= 12 && $current_hour < 15) $active_category = "Lunch";
elseif ($current_hour >= 16 && $current_hour < 19) $active_category = "Evening Snacks";
elseif ($current_hour >= 19 && $current_hour < 22) $active_category = "Dinner";
elseif ($current_hour >= 22 || $current_hour < 6) $active_category = "Night Food";

$categories = [
    "Morning / Breakfast" => "fa-mug-hot",
    "Lunch" => "fa-bowl-rice",
    "Evening Snacks" => "fa-cookie",
    "Dinner" => "fa-leaf",
    "Night Food" => "fa-moon",
    "Other Food Items" => "fa-apple-alt"
];

// Fetch recent orders for the bottom tracker
$my_recent_orders = $conn->query("
    SELECT co.*, cm.item_name 
    FROM canteen_orders co
    JOIN canteen_menu cm ON co.menu_id = cm.menu_id
    WHERE co.patient_id = $user_id
    ORDER BY co.created_at DESC LIMIT 3
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Ordering - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #020617;
            --card-bg: #0f172a;
            --primary-blue: #3b82f6;
            --text-gray: #94a3b8;
            --success-bg: rgba(6, 78, 59, 0.4);
            --success-text: #10b981;
            --border-color: rgba(255, 255, 255, 0.05);
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-dark); color: #fff; margin: 0; padding: 40px 5%; line-height: 1.6; }
        
        h1 { font-size: 32px; font-weight: 700; margin-bottom: 5px; }
        .subtitle { font-size: 16px; color: var(--text-gray); margin-bottom: 40px; }

        /* Success Banner matching image style */
        .msg-banner {
            background: rgba(16, 185, 129, 0.05);
            border: 1px solid #059669;
            color: #10b981;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .msg-error { background: rgba(239, 68, 68, 0.05); border-color: #dc2626; color: #ef4444; }

        .category-section { margin-bottom: 50px; }
        .category-header { 
            display: flex; align-items: center; justify-content: space-between;
            padding-bottom: 15px; border-bottom: 1px solid var(--border-color);
            margin-bottom: 30px;
        }
        .category-title { font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
        .category-title i { color: var(--primary-blue); opacity: 0.8; }
        .serving-badge { font-size: 11px; font-weight: 800; padding: 4px 12px; border-radius: 20px; background: rgba(59, 130, 246, 0.1); color: var(--primary-blue); text-transform: uppercase; letter-spacing: 0.5px; }
        .closed-badge { background: rgba(255,255,255,0.05); color: #64748b; }

        .food-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        
        .food-card { 
            background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 15px; 
            padding: 25px; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; flex-direction: column; gap: 12px;
            position: relative;
        }
        .food-card:hover { transform: translateY(-5px); border-color: var(--primary-blue); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        
        .food-icon { font-size: 26px; color: var(--primary-blue); margin-bottom: 5px; }
        .food-name { font-size: 19px; font-weight: 700; margin: 0; }
        .food-desc { font-size: 13px; color: var(--text-gray); min-height: 40px; }
        
        .diet-controls { margin-top: 10px; display: flex; flex-direction: column; gap: 5px; }
        .diet-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .diet-select { background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); color: #fff; padding: 8px; border-radius: 8px; font-size: 12px; outline: none; }

        .price-row { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; pt: 10px; border-top: 1px solid var(--border-color); }
        .food-price { font-size: 20px; font-weight: 800; color: #fff; }
        
        .btn-order { 
            background: var(--primary-blue); color: #fff; border: none; padding: 12px 20px; border-radius: 10px;
            font-weight: 700; cursor: pointer; transition: 0.3s; font-size: 13px; text-align: center;
        }
        .btn-order:hover:not(:disabled) { background: #2563eb; transform: scale(1.05); }
        .btn-order:disabled { opacity: 0.3; cursor: not-allowed; }

        /* Bottom Status Tracker */
        .bottom-tracker { 
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%);
            background: #0f172a; border: 1px solid var(--border-color); border-radius: 20px;
            padding: 15px 30px; display: flex; gap: 30px; box-shadow: 0 10px 50px rgba(0,0,0,0.5);
            max-width: 90%; overflow-x: auto; z-index: 1000;
        }
        .tracker-item { min-width: 150px; border-right: 1px solid var(--border-color); padding-right: 20px; }
        .tracker-item:last-child { border: none; }
        .tracker-status { font-size: 9px; font-weight: 800; color: var(--primary-blue); text-transform: uppercase; margin-bottom: 2px; display: block; }
        .tracker-name { font-size: 12px; font-weight: 600; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        @media (max-width: 768px) {
            .bottom-tracker { bottom: 10px; padding: 10px 20px; gap: 15px; }
            body { padding: 30px 20px; }
        }
    </style>
</head>
<body>

    <a href="patient_dashboard.php" style="color: var(--text-gray); text-decoration: none; font-size: 14px; margin-bottom: 20px; display: inline-block;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

    <h1>Food Ordering</h1>
    <p class="subtitle">Select your meal and we'll deliver it to your assigned bed.</p>

    <?php if ($message): ?>
        <div class="msg-banner <?php echo $msg_type == 'error' ? 'msg-error' : ''; ?>">
            <i class="fas <?php echo $msg_type == 'success' ? 'fa-check' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php 
    foreach ($categories as $cat => $icon): 
        $is_serving = ($cat == $active_category || $cat == "Other Food Items");
        
        $cat_items = $conn->query("SELECT * FROM canteen_menu WHERE item_category = '$cat'");
        if ($cat_items->num_rows == 0) continue;
    ?>
        <div class="category-section">
            <div class="category-header">
                <div class="category-title"><i class="fas <?php echo $icon; ?>"></i> <?php echo $cat; ?></div>
                <div class="serving-badge <?php echo !$is_serving ? 'closed-badge' : ''; ?>">
                    <?php echo $is_serving ? 'Now Serving' : 'Available Later'; ?>
                </div>
            </div>

            <div class="food-grid">
                <?php while ($item = $cat_items->fetch_assoc()): 
                    $is_sold_out = ($item['availability'] != 'Available');
                ?>
                    <form method="POST" class="food-card" style="<?php echo (!$is_serving || $is_sold_out) ? 'opacity: 0.6;' : ''; ?>">
                        <input type="hidden" name="menu_id" value="<?php echo $item['menu_id']; ?>">
                        <input type="hidden" name="food_item" value="<?php echo htmlspecialchars($item['item_name']); ?>">
                        <input type="hidden" name="price" value="<?php echo $item['price']; ?>">
                        <input type="hidden" name="place_order" value="1">

                        <div class="food-icon"><i class="fas <?php echo $icon; ?>"></i></div>
                        <h3 class="food-name"><?php echo htmlspecialchars($item['item_name']); ?></h3>
                        <p class="food-desc"><?php echo htmlspecialchars($item['description']); ?></p>

                        <div class="diet-controls">
                            <span class="diet-label">Select Diet Type</span>
                            <select name="requested_diet" class="diet-select">
                                <option value="Normal">Normal</option>
                                <option value="Diabetic">Diabetic Friendly</option>
                                <option value="Low-Salt">Low-Salt</option>
                            </select>
                        </div>

                        <div class="price-row">
                            <span class="food-price">₹<?php echo number_format($item['price'], 0); ?></span>
                            <button type="submit" class="btn-order" <?php echo (!$is_serving || $is_sold_out) ? 'disabled' : ''; ?>>
                                <?php 
                                    if ($is_sold_out) echo "Sold Out";
                                    elseif (!$is_serving) echo "Closed";
                                    else echo "Order Now";
                                ?>
                            </button>
                        </div>
                    </form>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if ($my_recent_orders && $my_recent_orders->num_rows > 0): ?>
        <div class="bottom-tracker">
            <div style="font-size: 11px; font-weight: 800; color: #64748b; writing-mode: vertical-rl; text-orientation: mixed; border-right: 1px solid rgba(255,255,255,0.1); padding-right: 10px; margin-right: 10px;">TRACKER</div>
            <?php while ($o = $my_recent_orders->fetch_assoc()): ?>
                <div class="tracker-item">
                    <span class="tracker-status"><?php echo $o['order_status']; ?></span>
                    <span class="tracker-name"><?php echo htmlspecialchars($o['item_name']); ?></span>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

</body>
</html>
