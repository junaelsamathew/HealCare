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

$sql = "SELECT * FROM canteen_menu WHERE availability = 'Available'";
if ($category_filter != 'All Items') {
    $sql .= " AND item_category = '" . $conn->real_escape_string($category_filter) . "'";
}
if (!empty($search_query)) {
    $sql .= " AND item_name LIKE '%" . $conn->real_escape_string($search_query) . "%'";
}

$menu_items = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canteen Menu - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0a0f1d;
            --sidebar-bg: #0f172a;
            --accent-blue: #3b82f6;
            --card-bg: #1e293b;
            --text-light: #f1f5f9;
            --text-dim: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.05);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-light);
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
        }

        /* Top Bar matching dashboard */
        .top-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: var(--sidebar-bg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            z-index: 1000;
        }

        .logo { font-size: 22px; font-weight: 800; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .logo i { color: var(--accent-blue); }

        .search-box {
            background: #1e293b;
            padding: 8px 20px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            width: 350px;
            border: 1px solid var(--border-color);
        }
        .search-box input {
            background: none;
            border: none;
            color: #fff;
            outline: none;
            width: 100%;
            font-size: 14px;
        }
        .search-box i { color: var(--text-dim); }

        /* Navigation */
        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        .nav-item {
            color: var(--text-dim);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-item:hover, .nav-item.active { color: var(--accent-blue); }
        
        .cart-link { position: relative; }
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -12px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 10px;
        }

        /* Main Content */
        .main-content {
            margin-top: 70px;
            padding: 40px;
            width: 100%;
        }

        .page-header {
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .page-header h1 { font-size: 28px; font-weight: 700; margin: 0; }
        .page-header p { color: var(--text-dim); margin: 5px 0 0; }

        /* Food Grid */
        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 25px;
        }

        .food-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            position: relative;
            aspect-ratio: 1 / 1.1;
            overflow: hidden;
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
        }
        .food-card:hover { 
            transform: translateY(-10px); 
            border-color: var(--accent-blue);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }

        .food-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0.5;
            transition: 0.5s;
        }
        .food-card:hover img { opacity: 0.7; transform: scale(1.1); }

        .card-content {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(to top, rgba(10,15,29,0.95) 0%, rgba(10,15,29,0.3) 100%);
            padding: 20px;
            text-align: center;
        }

        .food-name {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            margin: 0;
            text-shadow: 0 4px 10px rgba(0,0,0,0.5);
        }
        .food-price {
            font-size: 18px;
            font-weight: 600;
            color: var(--accent-blue);
            margin-top: 10px;
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
            padding: 12px;
            border-radius: 12px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
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
            z-index: 2000;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            display: none;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }

        /* Filter Pills */
        .filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            overflow-x: auto;
            padding-bottom: 10px;
        }
        .filter-pill {
            background: var(--sidebar-bg);
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
    </style>
</head>
<body>

    <header class="top-bar">
        <a href="patient_dashboard.php" class="logo">
            <i class="fas fa-heartbeat"></i> HealCare
        </a>

        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="search-input" placeholder="What would you like to eat?" value="<?php echo htmlspecialchars($search_query); ?>" onkeyup="handleSearch(event)">
        </div>

        <nav class="nav-links">
            <a href="canteen.php" class="nav-item <?php echo $category_filter == 'All Items' ? 'active' : ''; ?>">
                <i class="fas fa-utensils"></i> Full Menu
            </a>
            <a href="cart.php" class="nav-item cart-link" id="nav-cart">
                <i class="fas fa-shopping-basket"></i> Bag
                <span class="cart-badge" id="cart-count" style="<?php echo $cart_count == 0 ? 'display:none;' : ''; ?>"><?php echo $cart_count; ?></span>
            </a>
            <a href="patient_dashboard.php?section=orders" class="nav-item">
                <i class="fas fa-history"></i> My Orders
            </a>
            <div style="width: 1px; height: 30px; background: var(--border-color);"></div>
            <span style="font-size: 13px; font-weight:600; margin-left: 10px;"><?php echo htmlspecialchars($user_name); ?></span>
        </nav>
    </header>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>Canteen Menu</h1>
                <p>Nutritious & delicious meals delivered to your bedside.</p>
            </div>
        </div>

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

        <div class="food-grid">
            <?php 
            if ($menu_items && $menu_items->num_rows > 0):
                while ($item = $menu_items->fetch_assoc()): 
                    $img = $item['image_url'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400';
                ?>
                    <div class="food-card">
                        <img src="<?php echo $img; ?>" 
                             alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                             onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400';">
                        <div class="card-content">
                            <h3 class="food-name"><?php echo htmlspecialchars($item['item_name']); ?></h3>
                            <div class="food-price">₹<?php echo number_format($item['price'], 0); ?></div>
                        </div>

                        <div class="card-overlay">
                            <button class="btn-action btn-order" onclick="event.stopPropagation(); location.href='place_order_details.php?id=<?php echo $item['menu_id']; ?>'">Order Now</button>
                            <button class="btn-action btn-cart" onclick="event.stopPropagation(); addToCart(this, <?php echo $item['menu_id']; ?>)">Add to Bag</button>
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
