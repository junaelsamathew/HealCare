<?php
include 'includes/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        h1 { color: #2b50c0; }
        p { color: #555; font-size: 1.1rem; }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 25px;
            background-color: #2b50c0;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
        }
        .btn:hover { background-color: #1e40af; }
        .error { color: #e74c3c; }
        .success { color: #27ae60; }
    </style>
</head>
<body>

<div class="container">
    <?php
    if (isset($_GET['token'])) {
        $token = mysqli_real_escape_string($conn, $_GET['token']);

        // Find user with this token
        $result = $conn->query("SELECT * FROM users WHERE verification_token = '$token' AND status = 'Unverified'");

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_id = $user['user_id'];

            // Activate user
            $update = $conn->query("UPDATE users SET status='Active', verification_token=NULL WHERE user_id=$user_id");

            if ($update) {
                echo '<div class="icon success">
                        <svg width="60" height="60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                      </div>';
                echo '<h1>Email Verified!</h1>';
                echo '<p>Your account has been successfully verified. You can now log in.</p>';
                echo '<a href="login.php" class="btn">Login Now</a>';
            } else {
                echo '<h1 class="error">Error!</h1>';
                echo '<p>Something went wrong while verifying your account. Please try again.</p>';
            }
        } else {
            echo '<h1 class="error">Invalid Link</h1>';
            echo '<p>This verification link is invalid or has already been used.</p>';
            echo '<a href="login.php" class="btn">Go to Login</a>';
        }
    } else {
        echo '<h1 class="error">Missing Token</h1>';
        echo '<p>No verification token provided.</p>';
    }
    ?>
</div>

</body>
</html>
