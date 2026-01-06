<?php
session_start();
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['force_change'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - HealCare</title>
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
        .change-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 40px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 450px;
            width: 100%;
            text-align: center;
        }
        h2 { font-size: 1.8rem; margin-bottom: 10px; font-weight: 700; }
        p { font-size: 0.9rem; opacity: 0.7; margin-bottom: 30px; }
        .input-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 8px; font-size: 0.85rem; opacity: 0.9; }
        input {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 1rem;
            outline: none;
        }
        input:focus { border-color: #3b82f6; }
        .btn-submit {
            background: #3b82f6;
            color: #fff;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="change-card">
        <h2>Change Password</h2>
        <p>Your account was recently approved. For security, please set a personal password before continuing.</p>
        
        <form action="auth_handler.php" method="POST">
            <input type="hidden" name="action" value="update_forced_password">
            <div class="input-group">
                <label>New Password</label>
                <input type="password" name="new_password" required minlength="8" placeholder="At least 8 characters">
            </div>
            <div class="input-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required placeholder="Repeat your new password">
            </div>
            <button type="submit" class="btn-submit">Update & Continue</button>
        </form>
    </div>
</body>
</html>
