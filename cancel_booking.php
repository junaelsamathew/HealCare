<?php session_start(); include 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Booking - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f0f4f8; font-family: 'Poppins', sans-serif; }
        .cancel-container {
            max-width: 600px;
            margin: 60px auto;
            background: #e6f7ff; /* Light Blue background from image */
            padding: 40px;
            border-radius: 8px;
            border: 1px solid #dbeafe;
            text-align: center;
        }
        .page-title {
            text-align: center;
            font-size: 2rem;
            color: #1e293b;
            margin-top: 40px;
            margin-bottom: 20px;
        }
        .warning-text {
            color: #ef4444; /* Red color */
            font-size: 0.95rem;
            margin-bottom: 30px;
            font-weight: 500;
        }
        .form-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        .form-group label {
            font-weight: 600;
            color: #1e293b;
        }
        .input-booking {
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            width: 250px;
        }
        .btn-proceed {
            background: #f97316; /* Orange button */
            color: white;
            padding: 10px 40px;
            border: none;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            box-shadow: 0 4px 6px rgba(249, 115, 22, 0.2);
        }
        .btn-proceed:hover {
            background: #ea580c;
        }
    </style>
</head>
<body>

    <h1 class="page-title">Cancel Booking</h1>

    <div class="cancel-container">
        <p class="warning-text">* Please Note, Cancellation allowed only before allowed time frame</p>
        
        <form method="POST" action="index.php"> <!-- Just demo redirect -->
            <div class="form-group">
                <label>Booking number</label>
                <input type="text" class="input-booking" name="booking_no" required>
            </div>
            
            <button type="submit" class="btn-proceed" onclick="alert('Demo: Request Submitted');">Proceed</button>
        </form>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
