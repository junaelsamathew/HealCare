<?php
session_start();
include 'includes/db_connect.php';

$email = $_GET['email'] ?? '';
if (!$email) {
    header("Location: signup.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Account - HealCare</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --accent: #60a5fa;
            --bg-deep: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top right, #1e3a8a, var(--bg-deep));
            overflow: hidden;
            position: relative;
        }

        /* Decorative Elements */
        body::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: var(--primary);
            filter: blur(150px);
            border-radius: 50%;
            top: -100px;
            right: -100px;
            opacity: 0.3;
        }

        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: #1d4ed8;
            filter: blur(180px);
            border-radius: 50%;
            bottom: -150px;
            left: -150px;
            opacity: 0.2;
        }

        .verification-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 450px;
            padding: 20px;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .glass-card {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .logo-area {
            margin-bottom: 30px;
        }

        .logo-area .icon-badge {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px;
            color: #fff;
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
            transform: rotate(-5deg);
        }

        h2 {
            color: #fff;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        p.description {
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .user-email {
            color: var(--primary);
            font-weight: 600;
        }

        .otp-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 35px;
        }

        .otp-input {
            width: 60px;
            height: 70px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            font-size: 32px;
            font-weight: 700;
            color: #fff;
            text-align: center;
            transition: all 0.3s;
            outline: none;
        }

        .otp-input:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.3);
            transform: translateY(-2px);
        }

        .btn-verify {
            width: 100%;
            padding: 16px;
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            border: none;
            border-radius: 16px;
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px rgba(59, 130, 246, 0.4);
            background: linear-gradient(to right, var(--primary-dark), var(--primary));
        }

        .btn-verify:active {
            transform: translateY(0);
        }

        .footer-links {
            margin-top: 30px;
            font-size: 14px;
            color: #64748b;
        }

        .footer-links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .footer-links a:hover {
            color: var(--accent);
            text-decoration: underline;
        }

        /* Loading Spinner */
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .back-link {
            position: absolute;
            top: 40px;
            left: 40px;
            color: #fff;
            text-decoration: none;
            opacity: 0.6;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .back-link:hover {
            opacity: 1;
            transform: translateX(-5px);
        }

        @media (max-width: 480px) {
            .glass-card { padding: 30px 20px; }
            .otp-input { width: 50px; height: 60px; font-size: 24px; }
        }
    </style>
</head>
<body>

    <a href="signup.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Registration
    </a>

    <div class="verification-container">
        <div class="glass-card">
            <div class="logo-area">
                <div class="icon-badge">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <h2>Activate Account</h2>
                <p class="description">
                    We've sent a 4-digit verification code to<br>
                    <span class="user-email"><?php echo htmlspecialchars($email); ?></span>
                </p>
            </div>

            <form id="otpForm" action="auth_handler.php" method="POST">
                <input type="hidden" name="action" value="verify_signup_otp">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div class="otp-group">
                    <input type="text" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" id="otp1" required autocomplete="off">
                    <input type="text" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" id="otp2" required autocomplete="off">
                    <input type="text" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" id="otp3" required autocomplete="off">
                    <input type="text" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" id="otp4" required autocomplete="off">
                </div>
                
                <input type="hidden" name="otp" id="fullOtp">
                
                <button type="submit" class="btn-verify" id="submitBtn">
                    <span>Verify & Activate</span>
                    <div class="spinner" id="btnSpinner"></div>
                </button>
            </form>

            <div class="footer-links">
                Didn't receive the code? <a href="javascript:void(0)" id="resendBtn">Resend Code</a><br>
                <span id="countdown" style="font-size: 12px; display: block; margin-top: 10px; opacity: 0.7;">Resend available in 00:59</span>
            </div>
        </div>
    </div>

    <script>
        const inputs = document.querySelectorAll('.otp-input');
        const form = document.getElementById('otpForm');
        const fullOtpInput = document.getElementById('fullOtp');
        const submitBtn = document.getElementById('submitBtn');
        const btnSpinner = document.getElementById('btnSpinner');
        const resendBtn = document.getElementById('resendBtn');
        const countdownEl = document.getElementById('countdown');

        // Handle OTP inputs
        inputs.forEach((input, index) => {
            if (index === 0) input.focus();
            input.addEventListener('input', (e) => {
                if (e.target.value.length === 1 && index < inputs.length - 1) inputs[index + 1].focus();
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && e.target.value.length === 0 && index > 0) inputs[index - 1].focus();
            });
            input.addEventListener('keypress', (e) => { if (!/[0-9]/.test(e.key)) e.preventDefault(); });
        });

        // Resend Logic
        resendBtn.style.pointerEvents = 'none';
        resendBtn.style.opacity = '0.5';

        resendBtn.onclick = async function() {
            resendBtn.textContent = 'Sending...';
            resendBtn.style.pointerEvents = 'none';
            
            try {
                const formData = new FormData();
                formData.append('action', 'resend_signup_otp');
                formData.append('email', '<?php echo $email; ?>');

                const response = await fetch('auth_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.text();

                if (result.includes('OTP_SENT')) {
                    alert('A new verification code has been sent to your email.');
                    startTimer(59);
                } else {
                    alert('Error sending code. Please try again later.');
                    resendBtn.textContent = 'Resend Code';
                    resendBtn.style.pointerEvents = 'auto';
                }
            } catch (error) {
                alert('Connection error.');
                resendBtn.textContent = 'Resend Code';
                resendBtn.style.pointerEvents = 'auto';
            }
        };

        function startTimer(timeLeft) {
            resendBtn.style.pointerEvents = 'none';
            resendBtn.style.opacity = '0.5';
            
            const timer = setInterval(() => {
                if(timeLeft <= 0) {
                    clearInterval(timer);
                    countdownEl.innerHTML = 'You can now resend the code.';
                    resendBtn.textContent = 'Resend Code';
                    resendBtn.style.pointerEvents = 'auto';
                    resendBtn.style.opacity = '1';
                } else {
                    countdownEl.innerText = `Resend available in 00:${timeLeft < 10 ? '0' : ''}${timeLeft}`;
                    timeLeft--;
                }
            }, 1000);
        }

        startTimer(59);

        form.onsubmit = function(e) {
            const otpValue = Array.from(inputs).map(input => input.value).join('');
            if (otpValue.length < 4) {
                e.preventDefault();
                alert('Please enter the complete 4-digit code.');
                return false;
            }
            fullOtpInput.value = otpValue;
            submitBtn.querySelector('span').textContent = 'Verifying...';
            btnSpinner.style.display = 'block';
            submitBtn.disabled = true;
            return true;
        };
    </script>

</body>
</html>
