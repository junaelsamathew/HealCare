<?php
session_start();
include 'includes/db_connect.php';

// Mock Packages Data (Normally fetched from DB)
$packages = [
    [
        'id' => 1, 
        'name' => 'Basic Health Checkup', 
        'desc' => 'Complete basic health screening', 
        'tests' => 'CBC, Blood Sugar, Blood Pressure, Urine Test', 
        'price' => 1200.00, 
        'original_price' => 1500.00,
        'icon' => 'fas fa-user-check'
    ],
    [
        'id' => 2, 
        'name' => 'Comprehensive Package', 
        'desc' => 'Advanced health screening with imaging', 
        'tests' => 'CBC, Lipid Profile, Kidney, Liver, ECG, X-Ray', 
        'price' => 2625.00, 
        'original_price' => 3500.00,
        'icon' => 'fas fa-heartbeat'
    ],
    [
        'id' => 3, 
        'name' => 'Diabetes Care Package', 
        'desc' => 'Complete diabetes monitoring', 
        'tests' => 'HbA1c, Fasting Sugar, PP Sugar, Kidney Function', 
        'price' => 1700.00, 
        'original_price' => 2000.00,
        'icon' => 'fas fa-notes-medical'
    ]
];

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // In a real app, integrate payment gateway here
    $pkg_name = $_POST['package_name'];
    $date = $_POST['checkup_date'];
    $message = "<strong>Booking Confirmed!</strong><br>Package: $pkg_name<br>Date: $date<br>Transaction ID: TXN-" . rand(100000,999999);
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
                <span class="current-price">₹<?php echo $pkg['price']; ?></span>
                <span class="original-price">₹<?php echo $pkg['original_price']; ?></span>
                <span class="discount-badge"><?php echo round((($pkg['original_price']-$pkg['price'])/$pkg['original_price'])*100); ?>% OFF</span>
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
                
                <div class="form-group">
                    <label>Preferred Date</label>
                    <input type="date" name="checkup_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Payment Method</label>
                    <select class="form-control" required>
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
        const modal = document.getElementById('bookingModal');
        const modalPkgName = document.getElementById('modalPkgName');
        const inputPkgName = document.getElementById('inputPkgName');
        const modalPkgPrice = document.getElementById('modalPkgPrice');

        function openBooking(name, price) {
            modalPkgName.textContent = name;
            inputPkgName.value = name;
            modalPkgPrice.textContent = price;
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
    </script>
</body>
</html>
