<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'] ?? 'Patient';

// Fetch orders
$query = "
    SELECT co.order_id, co.order_date, co.order_time, co.order_status, co.total_amount, co.quantity, co.delivery_location,
           cm.item_name, cm.image_url, cm.item_category
    FROM canteen_orders co
    LEFT JOIN canteen_menu cm ON co.menu_id = cm.menu_id
    WHERE co.patient_id = ?
    ORDER BY co.order_date DESC, co.order_time DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
        :root {
            --accent-blue: #3b82f6;
            --card-bg: #1e293b;
            --text-light: #f1f5f9;
            --text-dim: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.05);
        }

        .order-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
            display: grid;
            grid-template-columns: 80px 1fr auto;
            gap: 20px;
            align-items: center;
        }

        .order-img {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            object-fit: cover;
            background: #0f172a;
        }

        .order-info h3 {
            margin: 0 0 5px 0;
            color: #fff;
            font-size: 16px;
        }

        .order-meta {
            color: var(--text-dim);
            font-size: 13px;
            margin-bottom: 5px;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-Placed { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .status-Preparing { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .status-Ready { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-Delivered { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-Cancelled { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        .price-tag {
            font-weight: 700;
            color: #fff;
            font-size: 15px;
        }
        
        
        /* Brand Animation */
        .brand-letter {
            display: inline-block;
            opacity: 0;
            transform: translateY(-5px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .brand-letter.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <!-- Universal Header -->
    <div class="reception-top-bar" style="background: #fff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee;">
        <a href="index.php" class="logo-main" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
            <img src="images/healcare_logo.jpg" alt="HealCare" style="height: 50px;">
            <span class="animated-brand" style="color: #020617; font-weight: 800; letter-spacing: -1px; font-size: 24px; margin: 0;">HEALCARE HOSPITAL</span>
        </a>
        <div style="display: flex; gap: 40px; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #020617; display: flex; align-items: center; justify-content: center; color: #020617;">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 10px; font-weight: 800; color: #020617; text-transform: uppercase; letter-spacing: 0.5px;">WHATSAPP</span>
                    <a href="https://wa.me/919539045609" target="_blank" style="font-size: 13px; color: #25d366; font-weight: 600; text-decoration: none;"><i class="fab fa-whatsapp"></i> +91 953 904 5609</a>
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
        <div style="flex: 1;"></div>
        <div class="user-controls">
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
                <a href="my_packages.php" class="nav-link"><i class="fas fa-box-medical"></i> My Health Packages</a>
                <a href="patient_lab_results.php" class="nav-link"><i class="fas fa-flask"></i> Lab Reports</a>
                <a href="prescriptions.php" class="nav-link"><i class="fas fa-pills"></i> Prescriptions</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
                <a href="canteen.php" class="nav-link"><i class="fas fa-utensils"></i> Canteen</a>
                <a href="my_orders.php" class="nav-link active"><i class="fas fa-receipt"></i> My Orders</a>
                <a href="patient_feedback.php" class="nav-link"><i class="fas fa-comment-dots"></i> Patient Feedback</a>
                <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Profile</a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="dashboard-header">
                <h1>My Orders</h1>
                <p>Track your canteen orders and delivery status.</p>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="orders-list">
                    <?php while($order = $result->fetch_assoc()): 
                        $fallback = 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600';
                        $img = !empty($order['image_url']) ? $order['image_url'] : $fallback;
                    ?>
                    <div class="order-card">
                        <img src="<?php echo $img; ?>" alt="Food Item" class="order-img" onerror="this.src='<?php echo $fallback; ?>'">
                        <div class="order-info">
                            <h3><?php echo htmlspecialchars($order['item_name']); ?> (x<?php echo $order['quantity']; ?>)</h3>
                            <div class="order-meta">
                                <span><i class="far fa-calendar"></i> <?php echo date('d M Y', strtotime($order['order_date'])); ?></span> &bull; 
                                <span><i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($order['order_time'])); ?></span>
                            </div>
                            <div class="order-meta">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($order['delivery_location']); ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div class="price-tag">₹<?php echo number_format($order['total_amount'], 2); ?></div>
                            <div style="margin-top: 5px;">
                                <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                    <?php echo $order['order_status']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: var(--text-dim);">
                    <i class="fas fa-utensils" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <p>You haven't placed any orders yet.</p>
                    <a href="canteen.php" style="color: var(--accent-blue); text-decoration: none; font-weight: 600;">Brows Menu</a>
                </div>
            <?php endif; ?>

        </main>
    </div>    <script>
        // Brand Animation
        document.addEventListener('DOMContentLoaded', function() {
            function initBrandAnimation() {
                const brandElement = document.querySelector('.animated-brand');
                if (!brandElement) return;

                const text = brandElement.textContent.trim();
                brandElement.textContent = ''; // Clear text

                // Create spans
                const letters = [];
                for (let char of text) {
                    const span = document.createElement('span');
                    span.textContent = char;
                    span.classList.add('brand-letter');
                    if (char === ' ') {
                        span.style.width = '0.3em'; // Space
                        span.style.display = 'inline-block';
                    }
                    brandElement.appendChild(span);
                    letters.push(span);
                }

                function animate() {
                    // Show sequence
                    letters.forEach((letter, index) => {
                        setTimeout(() => {
                            letter.classList.add('visible');
                        }, index * 100); // 100ms staggering
                    });

                    // Hide sequence (after full text is shown + delay)
                    const totalTime = (letters.length * 100) + 2000; // 2s pause

                    setTimeout(() => {
                        letters.forEach((letter, index) => {
                            setTimeout(() => {
                                letter.classList.remove('visible');
                            }, index * 50); // Faster fade out
                        });
                    }, totalTime);

                    const cycleTime = totalTime + (letters.length * 50) + 500; // time to hide + pause

                    setTimeout(animate, cycleTime);
                }

                // Start animation
                animate();
            }
            
            initBrandAnimation();
        });
    </script>
</body>
</html>
