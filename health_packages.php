<?php
session_start();
include 'includes/db_connect.php';

// Fetch Packages from Database
$packages_res = $conn->query("SELECT * FROM health_packages WHERE status = 'Active' ORDER BY created_at DESC");
$packages = [];
if ($packages_res && $packages_res->num_rows > 0) {
    while ($row = $packages_res->fetch_assoc()) {
        // Determine icon based on name
        $icon = 'fas fa-file-medical';
        if (stripos($row['package_name'], 'Basic') !== false) {
            $icon = 'fas fa-user-check';
        } elseif (stripos($row['package_name'], 'Comprehensive') !== false || stripos($row['package_name'], 'Executive') !== false) {
            $icon = 'fas fa-heartbeat';
        } elseif (stripos($row['package_name'], 'Diabetes') !== false) {
            $icon = 'fas fa-notes-medical';
        } elseif (stripos($row['package_name'], 'Wellness') !== false) {
            $icon = 'fas fa-spa';
        }
        
        $packages[] = [
            'id' => $row['package_id'],
            'name' => $row['package_name'],
            'desc' => $row['package_description'],
            'tests' => $row['included_tests'],
            'price' => $row['discounted_price'],
            'original_price' => $row['original_price'],
            'icon' => $icon
        ];
    }
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
        header("Location: login.php?redirect=health_packages.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $pkg_name = $_POST['package_name'];
    $pkg_price = floatval($_POST['package_price']);
    $date = $_POST['checkup_date'];
    
    $pmode = $_POST['payment_method'];
    
    // Create Billing Entry
    $bill_type = 'Health Package';
    $description = "Health Package Booking: $pkg_name for $date";
    $payment_status = 'Pending';
    $bill_date = date('Y-m-d');
    $doctor_id = NULL;
    $appt_id = NULL;
    $ref_id = NULL;
    
    $stmt = $conn->prepare("INSERT INTO billing (patient_id, doctor_id, appointment_id, reference_id, bill_type, description, total_amount, payment_status, bill_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiissdss", $user_id, $doctor_id, $appt_id, $ref_id, $bill_type, $description, $pkg_price, $payment_status, $bill_date);
    
    if ($stmt->execute()) {
        $bill_id = $conn->insert_id;
        
        if ($pmode == 'Pay at Hospital') {
            header("Location: billing.php?msg=booking_success&bill_id=" . $bill_id);
        } else {
            header("Location: payment_gateway.php?bill_id=" . $bill_id);
        }
        exit();
    } else {
        $message = "<span style='color:red;'>Error creating booking: " . $conn->error . "</span>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Packages - HealCare</title>
    <link rel="stylesheet" href="styles/landing.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8fafc; }
        .page-header {
            background: #1e40af;
            color: white;
            padding: 80px 0 60px;
            text-align: center;
        }
        .page-header h1 { font-size: 2.5rem; margin-bottom: 15px; }
        .page-header p { font-size: 1.1rem; opacity: 0.9; }

        .packages-grid {
            max-width: 1200px;
            margin: -40px auto 80px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }

        .package-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-color: #3b82f6;
        }
        .pkg-icon {
            width: 60px;
            height: 60px;
            background: #ecfdf5;
            color: #10b981;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        .pkg-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }
        .pkg-desc {
            color: #64748b;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        .pkg-tests {
            margin-bottom: 25px;
            flex-grow: 1;
        }
        .pkg-tests h4 {
            font-size: 0.9rem;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 10px;
        }
        .test-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .test-list li {
            padding-left: 20px;
            position: relative;
            margin-bottom: 5px;
            color: #475569;
            font-size: 0.9rem;
        }
        .test-list li::before {
            content: '✓';
            color: #10b981;
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        .price-tag {
            margin-bottom: 25px;
        }
        .current-price {
            font-size: 2rem;
            font-weight: 800;
            color: #1e293b;
        }
        .original-price {
            text-decoration: line-through;
            color: #94a3b8;
            font-size: 1.1rem;
            margin-left: 10px;
        }
        .discount-badge {
            background: #ffe4e6;
            color: #be123c;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            vertical-align: middle;
            margin-left: 10px;
        }
        .btn-select-pkg {
            width: 100%;
            padding: 15px;
            background: #1e40af;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-select-pkg:hover {
            background: #1e3a8a;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .modal {
            background: white;
            padding: 40px;
            border-radius: 16px;
            width: 100%;
            max-width: 500px;
            position: relative;
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #94a3b8;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 0.9rem; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; }
        .validation-msg { font-size: 11px; margin-top: 4px; display: none; transition: 0.3s; }
        .validation-msg.error { color: #ef4444; display: block; }
        .form-control.invalid { border-color: #ef4444 !important; }
        .form-control.valid { border-color: #10b981 !important; }
        
        .success-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }
        .success-box {
            background: white;
            color: #1e293b;
            padding: 40px;
            border-radius: 16px;
            max-width: 400px;
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <header class="page-header">
        <h1>Health Packages</h1>
        <p>Comprehensive checkups for a healthier you. Book directly.</p>
    </header>

    <div class="packages-grid">
        <?php foreach($packages as $pkg): ?>
        <div class="package-card">
            <div class="pkg-icon">
                <i class="<?php echo $pkg['icon']; ?>"></i>
            </div>
            <h3 class="pkg-name"><?php echo $pkg['name']; ?></h3>
            <p class="pkg-desc"><?php echo $pkg['desc']; ?></p>
            
            <div class="pkg-tests">
                <h4>Includes:</h4>
                <ul class="test-list">
                    <?php 
                    $tests = explode(',', $pkg['tests']); 
                    foreach($tests as $test) {
                        echo "<li>" . trim($test) . "</li>";
                    }
                    ?>
                </ul>
            </div>

            <div class="price-tag">
                <span class="current-price">₹<?php echo number_format($pkg['price'], 2); ?></span>
                <?php if ($pkg['original_price'] > $pkg['price']): ?>
                    <span class="original-price">₹<?php echo number_format($pkg['original_price'], 2); ?></span>
                    <span class="discount-badge"><?php echo round((($pkg['original_price'] - $pkg['price']) / $pkg['original_price']) * 100); ?>% OFF</span>
                <?php endif; ?>
            </div>

            <button class="btn-select-pkg" onclick="openBooking('<?php echo $pkg['name']; ?>', '<?php echo $pkg['price']; ?>')">Select Package</button>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Booking Modal -->
    <div class="modal-overlay" id="bookingModal">
        <div class="modal">
            <button class="close-modal" onclick="closeModal()">×</button>
            <h2 style="margin-top:0;">Book Package</h2>
            <p style="color:#64748b; margin-bottom: 25px;">You are booking: <strong id="modalPkgName"></strong></p>
            
            <form method="POST">
                <input type="hidden" name="package_name" id="inputPkgName">
                <input type="hidden" name="package_price" id="inputPkgPrice">
                
                <div class="form-group">
                    <label>Preferred Date</label>
                    <input type="date" id="package_date" name="checkup_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    <div id="date_msg" class="validation-msg"></div>
                </div>

                <div class="form-group">
                    <label>Payment Method</label>
                    <select class="form-control" name="payment_method" required>
                        <option>Credit/Debit Card</option>
                        <option>UPI / GPay</option>
                        <option>Net Banking</option>
                        <option>Pay at Hospital</option>
                    </select>
                </div>

                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; font-weight: 700;">
                        <span>Total to Pay:</span>
                        <span>₹<span id="modalPkgPrice"></span></span>
                    </div>
                </div>

                <button type="submit" class="btn-select-pkg">Confirm & Pay</button>
            </form>
        </div>
    </div>

    <!-- Success Message -->
    <?php if($message): ?>
    <div class="success-overlay" onclick="this.style.display='none'">
        <div class="success-box">
            <i class="fas fa-check-circle" style="font-size: 3rem; color: #10b981; margin-bottom: 20px;"></i>
            <h3 style="margin: 0 0 10px;">Success!</h3>
            <p><?php echo $message; ?></p>
            <button class="btn-select-pkg" style="margin-top: 20px;" onclick="window.location.href='index.php'">Return Home</button>
        </div>
    </div>
    <?php endif; ?><?php include 'includes/footer.php'; ?>

    <script>
        const isLoggedIn = <?php echo isset($_SESSION['logged_in']) ? 'true' : 'false'; ?>;
        const modal = document.getElementById('bookingModal');
        const modalPkgName = document.getElementById('modalPkgName');
        const inputPkgName = document.getElementById('inputPkgName');
        const modalPkgPrice = document.getElementById('modalPkgPrice');

        function openBooking(name, price) {
            if (!isLoggedIn) {
                window.location.href = 'login.php?redirect=' + encodeURIComponent('health_packages.php?open=' + encodeURIComponent(name));
                return;
            }
            modalPkgName.textContent = name;
            inputPkgName.value = name;
            modalPkgPrice.textContent = price;
            document.getElementById('inputPkgPrice').value = price;
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        // Live Date Validation
        const dateEl = document.getElementById('package_date');
        const dateMsg = document.getElementById('date_msg');

        if (dateEl) {
            dateEl.addEventListener('input', function() {
                const val = this.value;
                if (!val) {
                    this.classList.remove('invalid', 'valid');
                    dateMsg.style.display = 'none';
                    return;
                }

                const parts = val.split('-');
                const selectedDate = new Date(parts[0], parts[1] - 1, parts[2]);
                selectedDate.setHours(0,0,0,0);
                
                const today = new Date();
                today.setHours(0,0,0,0);
                
                const tomorrow = new Date(today);
                tomorrow.setDate(today.getDate() + 1);

                if (selectedDate < today) {
                    this.classList.remove('valid');
                    this.classList.add('invalid');
                    dateMsg.innerText = "Error: Past dates are not allowed.";
                    dateMsg.classList.add('error');
                    dateMsg.style.color = '#ef4444';
                    dateMsg.style.display = 'block';
                } else {
                    this.classList.remove('invalid');
                    this.classList.add('valid');
                    dateMsg.classList.remove('error');
                    dateMsg.style.display = 'block';
                    
                    if (selectedDate.getTime() === today.getTime()) {
                        dateMsg.innerText = "✓ Scheduled for Today";
                        dateMsg.style.color = '#10b981';
                    } else if (selectedDate.getTime() === tomorrow.getTime()) {
                        dateMsg.innerText = "✓ Scheduled for Tomorrow";
                        dateMsg.style.color = '#10b981';
                    } else {
                        dateMsg.innerText = "✓ Valid future date";
                        dateMsg.style.color = '#10b981';
                    }
                }
            });
            // Initial check
            dateEl.dispatchEvent(new Event('input'));
        }

        // Auto-open modal from URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const openPkg = urlParams.get('open');
            if (openPkg) {
                // Find pkg data if needed, or if price is fixed in code... 
                // Since prices are rendered in loop, maybe better to search buttons
                const buttons = document.querySelectorAll('.btn-select-pkg');
                buttons.forEach(btn => {
                    const onclickAttr = btn.getAttribute('onclick');
                    if (onclickAttr && onclickAttr.includes("openBooking('" + openPkg + "'")) {
                        btn.click();
                    }
                });
            }
        });
    </script>

    <style>
        /* Override chatbot position for patient pages - position at bottom */
        .chatbot-toggler {
            bottom: 30px !important;
        }
        
        .chatbot {
            bottom: 110px !important;
        }
        
        @media (max-width: 490px) {
            .chatbot-toggler {
                bottom: 20px !important;
            }
        }
    </style>
    <!-- Chatbot Widget -->
    <?php include 'includes/chatbot_widget.php'; ?>
</body>
</html>
