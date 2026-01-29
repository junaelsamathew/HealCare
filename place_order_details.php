<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$menu_id = $_GET['id'] ?? 0;
$requested_diet = $_GET['diet'] ?? 'Normal';

if (!$menu_id) {
    header("Location: canteen.php");
    exit();
}

// Fetch item details
$item_res = $conn->query("SELECT * FROM canteen_menu WHERE menu_id = $menu_id");
$item = $item_res->fetch_assoc();

if (!$item) {
    header("Location: canteen.php");
    exit();
}

// Fetch wards for dropdown
$wards_res = $conn->query("SELECT * FROM wards ORDER BY ward_name ASC");

$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_order'])) {
    $quantity = (int)$_POST['quantity'];
    
    // Combine Ward and Bed
    $ward_name = $_POST['ward_name'];
    $bed_no = $_POST['bed_no'];
    $location = $ward_name . " / " . $bed_no;
    
    $price = $item['price'];
    $total_amount = $price * $quantity;
    
    // Status: Pending Payment
    $stmt = $conn->prepare("INSERT INTO canteen_orders (patient_id, menu_id, quantity, order_date, order_time, order_status, total_amount, delivery_location) VALUES (?, ?, ?, CURDATE(), CURTIME(), 'Pending Payment', ?, ?)");
    $stmt->bind_param("iiids", $user_id, $menu_id, $quantity, $total_amount, $location);
    
    if ($stmt->execute()) {
        $order_id = $stmt->insert_id;
        // Redirect to Payment Gateway
        header("Location: canteen_payment.php?order_id=" . $order_id);
        exit();
    } else {
        $message = "Error: " . $conn->error;
        $msg_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Order - HealCare</title>
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
        
        .confirm-container { max-width: 600px; margin: 0 auto; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 20px; padding: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        
        h1 { font-size: 28px; font-weight: 700; margin-bottom: 30px; text-align: center; }
        
        .item-preview { display: flex; align-items: center; gap: 20px; padding: 20px; background: rgba(255,255,255,0.03); border-radius: 12px; margin-bottom: 30px; overflow: hidden; }
        .item-img { width: 100px; height: 100px; border-radius: 12px; object-fit: cover; border: 1px solid var(--border-color); }
        .item-info h3 { margin: 0; font-size: 20px; }
        .item-info p { margin: 5px 0 0; color: var(--text-gray); font-size: 14px; }

        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: var(--text-gray); margin-bottom: 10px; }
        .form-control { width: 100%; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 15px; color: #fff; font-size: 16px; outline: none; box-sizing: border-box; }
        .form-control:focus { border-color: var(--primary-blue); }
        
        select.form-control { -webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 1rem center; background-size: 1em; }

        .price-summary { margin: 30px 0; padding-top: 20px; border-top: 1px solid var(--border-color); }
        .price-row { display: flex; justify-content: space-between; font-size: 18px; margin-bottom: 10px; }
        .total-row { font-size: 24px; font-weight: 800; color: var(--primary-blue); margin-top: 10px; }

        .btn-confirm { width: 100%; background: var(--primary-blue); color: #fff; border: none; padding: 15px; border-radius: 12px; font-weight: 700; cursor: pointer; font-size: 16px; transition: 0.3s; margin-top: 20px; }
        .btn-confirm:hover { background: #2563eb; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3); }

        .back-link { display: block; text-align: center; margin-top: 20px; color: var(--text-gray); text-decoration: none; font-size: 14px; }
        .back-link:hover { color: #fff; }
        
        .row-group { display: flex; gap: 15px; }
        .col-half { flex: 1; }

        /* Dropdown Fix for Visibility */
        select option {
            background-color: #0f172a;
            color: white;
            padding: 10px;
        }
    </style>
</head>
<body>

    <div class="confirm-container">
        <h1>Confirm Your Order</h1>

        <?php if ($message): ?>
            <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="item-preview">
            <?php $img_url = $item['image_url'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400'; ?>
            <img src="<?php echo $img_url; ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" class="item-img" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400';">
            <div class="item-info">
                <h3><?php echo htmlspecialchars($item['item_name']); ?></h3>
                <p><?php echo htmlspecialchars($item['description']); ?> | <strong>Diet: <?php echo htmlspecialchars($requested_diet); ?></strong></p>
            </div>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" class="form-control" value="1" min="1" max="20" id="qty-input" onchange="updateTotal()">
            </div>

            <div class="form-group">
                <label>Delivery Location</label>
                <div class="row-group">
                    <div class="col-half">
                        <select name="ward_name" class="form-control" required>
                            <option value="" disabled selected>Select Ward</option>
                            <?php 
                            if($wards_res->num_rows > 0) {
                                while($row = $wards_res->fetch_assoc()) {
                                    echo '<option value="'.htmlspecialchars($row['ward_name']).'">'.htmlspecialchars($row['ward_name']).'</option>';
                                }
                            } else {
                                // Fallback if no wards
                                echo '<option value="General Ward A">General Ward A</option>';
                                echo '<option value="Private Ward B">Private Ward B</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-half">
                        <input type="text" name="bed_no" class="form-control" placeholder="Bed Number (e.g. 12)" required>
                    </div>
                </div>
            </div>

            <div class="price-summary">
                <div class="price-row">
                    <span>Unit Price</span>
                    <span>₹<?php echo number_format($item['price'], 2); ?></span>
                </div>
                <div class="price-row total-row">
                    <span>Total Amount</span>
                    <span id="total-amount">₹<?php echo number_format($item['price'], 2); ?></span>
                </div>
            </div>

            <button type="submit" name="confirm_order" class="btn-confirm">Proceed to Payment <i class="fas fa-arrow-right"></i></button>
            <a href="canteen.php" class="back-link">Cancel and Go Back</a>
        </form>
    </div>

    <script>
        function updateTotal() {
            const qty = document.getElementById('qty-input').value;
            const price = <?php echo $item['price']; ?>;
            const total = qty * price;
            document.getElementById('total-amount').textContent = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2});
        }
    </script></body>
</html>
