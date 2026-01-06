<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Application Status - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/main.css">
    <style>
        body {
            background-color: #0a192f;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            font-family: 'Poppins', sans-serif;
        }
        .status-form-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 40px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        h2 {
            font-size: 1.8rem;
            margin-bottom: 20px;
            font-weight: 700;
        }
        p {
            font-size: 0.9rem;
            opacity: 0.7;
            margin-bottom: 30px;
        }
        .input-group {
            margin-bottom: 25px;
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            opacity: 0.9;
        }
        input {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }
        input:focus {
            border-color: #3b82f6;
        }
        .btn-check {
            background: #1e40af;
            color: #fff;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-check:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }
        .back-link {
            display: block;
            margin-top: 20px;
            color: #4fc3f7;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="status-form-card">
        <h2>Check Status</h2>
        <p>Enter your email and the Application ID received during submission to track your progress.</p>
        
        <form action="pending_approval.php" method="GET">
            <div class="input-group">
                <label>Registered Email Address</label>
                <input type="email" name="email" placeholder="e.g. doctor@healcare.com" required>
            </div>
            <div class="input-group">
                <label>Application ID (e.g., HC-APP-2024-XXXX)</label>
                <input type="text" name="app_id" placeholder="Enter your ID" required>
            </div>
            <button type="submit" class="btn-check">View Application Status</button>
        </form>
        
        <a href="login.php" class="back-link">← Back to Login</a>
    </div>
</body>
</html>
