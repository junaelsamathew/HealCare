<?php session_start(); include 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f0f4f8; font-family: 'Poppins', sans-serif; }
        .page-container {
            max-width: 900px;
            margin: 80px auto;
            text-align: center;
            padding: 20px;
        }
        .page-title {
            font-size: 2.5rem;
            color: #1e293b;
            margin-bottom: 50px;
            font-weight: 600;
        }
        .cards-wrapper {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
        }
        .option-card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            width: 280px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .option-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: #3b82f6;
        }
        .icon-box {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            font-size: 3rem;
        }
        .icon-booking {
            background: #0f4c5c; /* Dark Teal from image */
            color: white;
        }
        .icon-cancel {
            background: #0f4c5c; /* Dark Teal from image */
            color: white;
        }
        .card-label {
            font-size: 1.2rem;
            font-weight: 600;
            color: #0f4c5c;
        }
    </style>
</head>
<body>

    <div class="page-container">
        <h1 class="page-title">Appointments</h1>
        
        <div class="cards-wrapper">
            <!-- Booking Card -->
            <a href="appointment_form.php" class="option-card">
                <div class="icon-box icon-booking">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <span class="card-label">Appointment Booking</span>
            </a>

            <!-- Cancel Card -->
            <a href="cancel_booking.php" class="option-card">
                <div class="icon-box icon-cancel">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <span class="card-label">Cancel Booking</span>
            </a>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
