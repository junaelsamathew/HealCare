<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item = $_POST['food_item'] ?? 'Order';
    $message = "Your order for $item has been placed! It will be delivered to your room shortly.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canteen Ordering - HealCare</title>
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .menu-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid var(--border-color);
            transition: 0.3s;
        }
        .menu-card:hover { transform: translateY(-5px); border-color: var(--primary-blue); }
        .menu-icon { font-size: 32px; color: var(--primary-blue); margin-bottom: 15px; }
        .menu-title { font-size: 18px; font-weight: 600; margin-bottom: 10px; }
        .menu-desc { font-size: 13px; color: var(--text-gray); margin-bottom: 20px; }
        .btn-order {
            width: 100%;
            padding: 10px;
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        .success-banner {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #10b981;
        }
    </style>
</head>
<body>
    <header class="top-header">
        <a href="index.php" class="logo-main">HEALCARE</a>
        <div class="header-info-group">
            <div class="header-info-item">
                <div class="info-icon-circle"><i class="fas fa-phone-alt"></i></div>
                <div class="info-details"><span class="info-label">EMERGENCY</span><span class="info-value">0717 783 146</span></div>
            </div>
        </div>
    </header>

    <header class="secondary-header">
        <div class="brand-section"><div class="brand-name">Canteen Services</div></div>
        <div class="user-controls"><a href="patient_dashboard.php" class="btn-logout">Back to Dashboard</a></div>
    </header>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <nav>
                <a href="patient_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="canteen.php" class="nav-link active"><i class="fas fa-utensils"></i> Canteen</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-header">
                <h1>Food Ordering</h1>
                <p>Select your meal and we'll deliver it to your assigned bed.</p>
            </div>

            <?php if ($message): ?>
                <div class="success-banner"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="menu-grid">
                <form method="POST" class="menu-card">
                    <input type="hidden" name="food_item" value="Healthy Breakfast Set">
                    <i class="fas fa-coffee menu-icon"></i>
                    <div class="menu-title">Breakfast Set</div>
                    <div class="menu-desc">Oatmeal, fresh fruits, and a choice of tea or coffee.</div>
                    <button type="submit" class="btn-order">Order Now</button>
                </form>

                <form method="POST" class="menu-card">
                    <input type="hidden" name="food_item" value="Nutritional Lunch">
                    <i class="fas fa-bowl-rice menu-icon"></i>
                    <div class="menu-title">Lunch Menu</div>
                    <div class="menu-desc">Brown rice, grilled chicken/tofu, and steamed vegetables.</div>
                    <button type="submit" class="btn-order">Order Now</button>
                </form>

                <form method="POST" class="menu-card">
                    <input type="hidden" name="food_item" value="Light Dinner">
                    <i class="fas fa-leaf menu-icon"></i>
                    <div class="menu-title">Dinner Menu</div>
                    <div class="menu-desc">Vegetable soup with whole-grain bread rolls.</div>
                    <button type="submit" class="btn-order">Order Now</button>
                </form>

                <form method="POST" class="menu-card">
                    <input type="hidden" name="food_item" value="Evening Snacks">
                    <i class="fas fa-cookie menu-icon"></i>
                    <div class="menu-title">Snacks & Beverages</div>
                    <div class="menu-desc">Nut bars, yogurt, and fresh fruit juices.</div>
                    <button type="submit" class="btn-order">Order Now</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
