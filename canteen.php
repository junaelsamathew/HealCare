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

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Use session data for name if available, otherwise fallback
$user_name = $_SESSION['full_name'] ?? 'Patient';

// Fetch cart count
$cart_count_res = $conn->query("SELECT COUNT(*) as count FROM canteen_cart WHERE user_id = $user_id");
$cart_count = $cart_count_res ? $cart_count_res->fetch_assoc()['count'] : 0;

// Filters
$category_filter = $_GET['category'] ?? 'All Items';
$search_query = $_GET['search'] ?? '';

// Initial query removed. Logic moved to display loop.
$menu_items = null; // Placeholder
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canteen Menu - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        /* Canteen Specific Overrides */
        :root {
            --accent-blue: #3b82f6;
            --card-bg: #1e293b;
            --text-light: #f1f5f9;
            --text-dim: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.05);
        }

        .search-box {
            background: #1e293b;
            padding: 10px 20px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            max-width: 400px;
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
        }
        .search-box input {
            background: none;
            border: none;
            color: #fff;
            font-size: 14px;
            outline: none;
            width: 100%;
        }

        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .food-card {
            background: var(--card-bg);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: 0.3s;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .food-card:hover {
            transform: translateY(-10px);
            border-color: var(--accent-blue);
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
        }
        
        .food-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            transition: 0.5s;
        }
        .food-card:hover img { transform: scale(1.1); opacity: 0.7; }

        .card-content {
            padding: 15px;
            text-align: center;
            background: linear-gradient(to top, var(--card-bg) 20%, transparent);
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 2;
        }

        .food-name {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.8);
            line-height: 1.2;
        }
        .food-price {
            font-size: 18px;
            font-weight: 800;
            color: var(--accent-blue);
            margin-top: 5px;
        }
        .food-meta {
            font-size: 11px;
            color: var(--text-dim);
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .cat-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--accent-blue);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            z-index: 4;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
        }

        /* Hover Overlay */
        .card-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10,15,29,0.8);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 15px;
            opacity: 0;
            transition: 0.3s;
            z-index: 5;
            backdrop-filter: blur(5px);
        }
        .food-card:hover .card-overlay { opacity: 1; }

        .btn-action {
            width: 80%;
            padding: 10px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            transition: 0.3s;
        }
        .btn-order { background: var(--accent-blue); color: #fff; }
        .btn-cart { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid var(--border-color); }
        .btn-action:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4); }

        /* Status Message */
        .msg-toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--accent-blue);
            color: white;
            padding: 15px 30px;
            border-radius: 15px;
            z-index: 9999;
            display: none;
            animation: slideIn 0.3s ease-out;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }

        /* Filter Pills */
        .filter-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }
        .filter-pill {
            background: #111d33;
            border: 1px solid var(--border-color);
            color: var(--text-dim);
            padding: 8px 20px;
            border-radius: 30px;
            white-space: nowrap;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: 0.3s;
        }
        .filter-pill:hover, .filter-pill.active {
            background: var(--accent-blue);
            color: #fff;
            border-color: var(--accent-blue);
        }

        .flying-item {
            position: fixed;
            z-index: 9999;
            width: 40px;
            height: 40px;
            background: var(--accent-blue);
            border-radius: 50%;
            pointer-events: none;
            transition: all 0.7s cubic-bezier(0.19, 1, 0.22, 1);
        }

        .cart-btn-header {
            position: relative;
            color: white;
            font-size: 18px;
            margin-right: 20px;
            text-decoration: none;
        }
        .cart-badge-header {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 800;
            padding: 2px 5px;
            border-radius: 50%;
            display: <?php echo $cart_count > 0 ? 'inline-block' : 'none'; ?>;
        }
    </style>
</head>
<body>
    <!-- Universal Header -->
    <div class="reception-top-bar" style="background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee;">
        <h1 style="color: #020617; font-weight: 800; letter-spacing: -1px; font-size: 24px; margin: 0;">+ HEALCARE</h1>
        <div style="display: flex; gap: 40px; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">EMERGENCY</span>
                    <span style="font-size: 13px; color: #3b82f6; font-weight: 600;">(+254) 717 783 146</span>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-clock"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">WORK HOUR</span>
                    <span style="font-size: 13px; color: #3b82f6; font-weight: 600;">09:00 - 20:00 Everyday</span>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">LOCATION</span>
                    <span style="font-size: 13px; color: #3b82f6; font-weight: 600;">Kanjirapally, Kottayam</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Navy Header -->
    <header class="secondary-header">
        <div class="brand-section">
            <div class="brand-icon">+</div>
            <div class="brand-name">HealCare</div>
        </div>
        <div class="user-controls">
            <!-- Cart Icon in Header -->
            <a href="cart.php" class="cart-btn-header" id="nav-cart">
                <i class="fas fa-shopping-bag"></i>
                <span class="cart-badge-header" id="cart-count"><?php echo $cart_count; ?></span>
            </a>
             <a href="my_orders.php" style="color: white; text-decoration: none; font-size: 14px; margin-right: 20px;"><i class="fas fa-history"></i> My Orders</a>

            <span class="user-greeting">Hello, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
            <a href="logout.php" class="btn-logout">Log Out</a>
        </div>
    </header>

    <div class="dashboard-layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <nav>
                <a href="patient_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="book_appointment.php" class="nav-link"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
                <a href="my_appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> My Appointments</a>
                <a href="medical_records.php" class="nav-link"><i class="fas fa-file-medical-alt"></i> Medical Records</a>
                <a href="prescriptions.php" class="nav-link"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link active"><i class="fas fa-utensils"></i> Canteen</a>
                <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h1>Canteen Menu</h1>
                    <p>Nutritious & delicious meals delivered to your bedside.</p>
                </div>
                
                <!-- Search Box -->
                 <div class="search-box">
                    <i class="fas fa-search" style="color: var(--text-dim);"></i>
                    <input type="text" placeholder="What would you like to eat?" onkeypress="handleSearch(event)">
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-container">
                <?php 
                $cats = ['All Items', 'Breakfast', 'Lunch', 'Dinner', 'Snacks', 'Beverages', 'Desserts', 'Patient Special'];
                foreach($cats as $cat):
                ?>
                    <a href="canteen.php?category=<?php echo urlencode($cat); ?>" class="filter-pill <?php echo $category_filter == $cat ? 'active' : ''; ?>">
                        <?php echo $cat; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div id="toast" class="msg-toast">Item added to bag!</div>

            <!-- Food Grid -->
            <div class="food-grid">
                <?php 
                $sql = "SELECT * FROM canteen_menu";
                if ($category_filter != 'All Items') {
                    $sql .= " WHERE item_category = '" . $conn->real_escape_string($category_filter) . "'";
                }
                if (!empty($search_query)) {
                    $sql .= ($category_filter == 'All Items' ? " WHERE" : " AND") . " item_name LIKE '%" . $conn->real_escape_string($search_query) . "%'";
                }
                
                $menu_items = $conn->query($sql);

                if ($menu_items && $menu_items->num_rows > 0):
                    while ($item = $menu_items->fetch_assoc()): 
                        $is_bev = (stripos($item['item_category'], 'Beverage') !== false || stripos($item['item_category'], 'Drink') !== false || in_array($item['item_name'], ['Boost', 'Horlicks', 'Tea', 'Coffee', 'Milk', 'Lassi', 'Buttermilk']));
                        $fallback = $is_bev ? 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=600' : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600';
                        $img = $item['image_url'] ?: $fallback;
                        $is_avail = ($item['availability'] == 'Available');
                        $card_opacity = $is_avail ? '1' : '0.6';
                        $btn_cursor = $is_avail ? 'pointer' : 'not-allowed';
                    ?>
                        <div class="food-card" style="opacity: <?php echo $card_opacity; ?>;">
                            <img src="<?php echo $img; ?>" 
                                 alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                                 onerror="this.onerror=null; this.src='<?php echo $fallback; ?>';"
                                 style="<?php echo !$is_avail ? 'filter: grayscale(100%);' : ''; ?>">
                            
                            <?php if (!$is_avail): ?>
                            <div style="position: absolute; top: 10px; right: 10px; background: #ef4444; color: white; padding: 5px 10px; border-radius: 5px; font-size: 11px; font-weight: 700; z-index: 10;">
                                OUT OF STOCK
                            </div>
                            <?php endif; ?>

                            <div class="cat-badge"><?php echo explode(' / ', $item['item_category'])[0]; ?></div>

                            <div class="card-content">
                                <h3 class="food-name"><?php echo htmlspecialchars($item['item_name']); ?></h3>
                                <div class="food-price">₹<?php echo number_format($item['price'], 0); ?></div>
                                <div class="food-meta">
                                    <span><i class="fas fa-carrot" style="color: #10b981;"></i> <?php echo $item['diet_type']; ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo $item['availability']; ?></span>
                                </div>
                            </div>

                            <div class="card-overlay">
                                <?php if ($is_avail): ?>
                                    <button class="btn-action btn-order" onclick="event.stopPropagation(); location.href='place_order_details.php?id=<?php echo $item['menu_id']; ?>'">Order Now</button>
                                    <button class="btn-action btn-cart" onclick="event.stopPropagation(); addToCart(this, <?php echo $item['menu_id']; ?>)">Add to Bag</button>
                                <?php else: ?>
                                    <button class="btn-action" style="background: #333; color: #999; cursor: not-allowed;" onclick="showToast('Sorry, this food is out of stock.')">Order Now</button>
                                    <button class="btn-action" style="background: #333; color: #999; cursor: not-allowed;" onclick="showToast('Sorry, this food is out of stock.')">Add to Bag</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; 
                else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 100px 0; color: var(--text-dim);">
                        <i class="fas fa-utensils-slash" style="font-size: 50px; margin-bottom: 20px; opacity: 0.3;"></i>
                        <p style="font-size: 18px; font-weight: 500;">No items found in this category.</p>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Debug: Found <?php echo $menu_items ? $menu_items->num_rows : '0'; ?> items for category <?php echo htmlspecialchars($category_filter); ?> -->
        </main>
    </div>

    <script>
    function handleSearch(e) {
        if (e.key === 'Enter') {
            const query = e.target.value;
            window.location.href = `canteen.php?search=${encodeURIComponent(query)}`;
        }
    }

    function addToCart(btn, menuId) {
        const card = btn.closest('.food-card');
        const img = card.querySelector('img');

        fetch('canteen_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add_to_cart&menu_id=${menuId}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                animateToCart(img);
                showToast('Added to your bag!');
                const badge = document.getElementById('cart-count');
                badge.textContent = data.count;
                badge.style.display = 'inline-block';
            }
        })
        .catch(err => console.error(err));
    }

    function animateToCart(img) {
        const cart = document.getElementById('nav-cart');
        const rect = img.getBoundingClientRect();
        const cartRect = cart.getBoundingClientRect();

        const flying = document.createElement('div');
        flying.className = 'flying-item';
        flying.style.left = rect.left + 'px';
        flying.style.top = rect.top + 'px';
        document.body.appendChild(flying);

        setTimeout(() => {
            flying.style.left = (cartRect.left + 10) + 'px';
            flying.style.top = (cartRect.top + 10) + 'px';
            flying.style.transform = 'scale(0.1)';
            flying.style.opacity = '0';
        }, 50);

        setTimeout(() => {
            flying.remove();
            cart.style.transform = 'scale(1.2)';
            setTimeout(() => cart.style.transform = 'scale(1)', 200);
        }, 750);
    }

    function showToast(text) {
        const toast = document.getElementById('toast');
        toast.textContent = text;
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }
    </script>
</body>
</html>
