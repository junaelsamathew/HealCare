<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'patient') {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Handle Feedback Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_feedback') {
    $data = json_decode($_POST['feedback_data'], true);
    
    $exp = $conn->real_escape_string($data[1]);
    $doc = $conn->real_escape_string($data[2]);
    $clean = (int)$data[3];
    $staff = $conn->real_escape_string($data[4]);
    $comment = $conn->real_escape_string($data[5]);
    
    $stmt = $conn->prepare("INSERT INTO patient_feedback (patient_id, experience, doctor_rating, cleanliness, staff_response, comments) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $user_id, $exp, $doc, $clean, $staff, $comment);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit;
    }
}

// Check if profile exists for name display
$res = $conn->query("SELECT * FROM patient_profiles WHERE user_id = $user_id");
$profile = ($res->num_rows > 0) ? $res->fetch_assoc() : null;
$display_name = $profile['name'] ?? $_SESSION['full_name'] ?? $username;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Feedback - HealCare</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    
    <style>
        /* Feedback Wizard Styles */
        .feedback-container {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .wizard-card {
            background: rgba(30, 41, 59, 1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            position: relative;
            min-height: 500px;
        }

        .preview-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.95));
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 30px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        /* Progress Bar */
        .progress-container {
            margin-bottom: 40px;
        }
        
        .progress-labels {
            display: flex;
            justify-content: space-between;
            color: #94a3b8;
            font-size: 12px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .progress-bar-bg {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: #3b82f6;
            width: 0%;
            transition: width 0.5s ease;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
        }

        /* Question Animation */
        .question-step {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .question-step.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .q-title {
            font-size: 24px;
            color: white;
            margin-bottom: 30px;
            line-height: 1.4;
        }

        /* Options Grid */
        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }

        .option-card {
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .option-card:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-2px);
        }

        .option-card.selected {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
        }

        .emoji-icon {
            font-size: 32px;
            margin-bottom: 10px;
            display: block;
        }
        
        .star-rating {
            display: flex;
            gap: 10px;
            font-size: 32px;
            color: #475569;
            cursor: pointer;
        }

        .star-rating i.active {
            color: #fbbf24;
            text-shadow: 0 0 10px rgba(251, 191, 36, 0.5);
        }

        /* Live Preview Items */
        .preview-item {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            opacity: 0.5;
            transition: all 0.3s;
        }

        .preview-item.filled {
            opacity: 1;
        }

        .preview-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            margin-bottom: 5px;
            display: block;
        }

        .preview-value {
            font-size: 15px;
            color: #fff;
            font-weight: 500;
        }

        /* Navigation Buttons */
        .nav-buttons {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }

        .btn-nav {
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }

        .btn-prev {
            background: transparent;
            color: #94a3b8;
        }
        
        .btn-prev:hover {
            color: white;
        }

        .btn-next {
            background: #3b82f6;
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .btn-next:disabled {
            background: #475569;
            color: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
        }

        .submit-success {
            text-align: center;
            padding: 40px;
        }

        @media (max-width: 900px) {
            .feedback-container {
                grid-template-columns: 1fr;
            }
                display: none;
            }
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
    
    <!-- Header -->
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
            <span class="user-greeting">Hello, <strong><?php echo htmlspecialchars($display_name); ?></strong></span>
            <a href="logout.php" class="btn-logout">Log Out</a>
        </div>
    </header>

    <div class="dashboard-layout">
        <!-- Sidebar -->
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
                <a href="patient_feedback.php" class="nav-link active"><i class="fas fa-comment-dots"></i> Patient Feedback</a>
                <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Profile</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="dashboard-header">
                <h1>Share Your Experience</h1>
                <p>Help us improve our care with your valuable feedback.</p>
            </div>

            <div class="feedback-container">
                
                <!-- Main Wizard -->
                <div class="wizard-card" id="wizardCard">
                    <!-- Progress -->
                    <div class="progress-container">
                        <div class="progress-labels">
                            <span id="progressText">Question 1 of 5</span>
                            <span id="progressPercent">20%</span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" id="progressBar" style="width: 20%;"></div>
                        </div>
                    </div>

                    <!-- Question 1: Experience -->
                    <div class="question-step active" id="step1">
                        <h2 class="q-title">How was your overall experience today?</h2>
                        <div class="options-grid">
                            <div class="option-card" onclick="selectOption(1, 'Excellent', '😊 Excellent')">
                                <span class="emoji-icon">😊</span>
                                <strong>Excellent</strong>
                            </div>
                            <div class="option-card" onclick="selectOption(1, 'Good', '🙂 Good')">
                                <span class="emoji-icon">🙂</span>
                                <strong>Good</strong>
                            </div>
                            <div class="option-card" onclick="selectOption(1, 'Average', '😐 Average')">
                                <span class="emoji-icon">😐</span>
                                <strong>Average</strong>
                            </div>
                            <div class="option-card" onclick="selectOption(1, 'Poor', '🙁 Poor')">
                                <span class="emoji-icon">🙁</span>
                                <strong>Poor</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Question 2: Doctor -->
                    <div class="question-step" id="step2">
                        <h2 class="q-title">Were you satisfied with the doctor consultation?</h2>
                        <div class="options-grid">
                            <div class="option-card" onclick="selectOption(2, 'Yes', 'Yes, Satisfied')">
                                <span class="emoji-icon">👨‍⚕️</span>
                                <strong>Yes, Satisfied</strong>
                            </div>
                            <div class="option-card" onclick="selectOption(2, 'Somewhat', 'Somewhat')">
                                <span class="emoji-icon">🤔</span>
                                <strong>Somewhat</strong>
                            </div>
                            <div class="option-card" onclick="selectOption(2, 'No', 'No, Not really')">
                                <span class="emoji-icon">👎</span>
                                <strong>No</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Question 3: Cleanliness -->
                    <div class="question-step" id="step3">
                        <h2 class="q-title">How would you rate the hospital cleanliness?</h2>
                        <div class="star-rating" id="starRating">
                            <i class="fas fa-star" onclick="rateStar(1)"></i>
                            <i class="fas fa-star" onclick="rateStar(2)"></i>
                            <i class="fas fa-star" onclick="rateStar(3)"></i>
                            <i class="fas fa-star" onclick="rateStar(4)"></i>
                            <i class="fas fa-star" onclick="rateStar(5)"></i>
                        </div>
                        <p style="margin-top: 15px; color: #94a3b8; font-size: 14px;" id="starText">Tap a star to rate</p>
                    </div>

                    <!-- Question 4: Staff -->
                    <div class="question-step" id="step4">
                        <h2 class="q-title">Did the nursing staff respond promptly to your needs?</h2>
                        <div class="options-grid" style="grid-template-columns: 1fr 1fr;">
                            <div class="option-card" onclick="selectOption(4, 'Yes', 'Yes, Quick Response')">
                                <span class="emoji-icon">⚡</span>
                                <strong>Yes</strong>
                            </div>
                            <div class="option-card" onclick="selectOption(4, 'No', 'No, It was slow')">
                                <span class="emoji-icon">🐢</span>
                                <strong>No</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Question 5: Comments -->
                    <div class="question-step" id="step5">
                        <h2 class="q-title">Any suggestions or comments you'd like to share?</h2>
                        <textarea id="commentBox" placeholder="Type your message here..." style="width: 100%; height: 150px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; padding: 15px; font-family: inherit; font-size: 14px; resize: none;" oninput="updateComment(this.value)"></textarea>
                    </div>

                    <!-- Success Message -->
                    <div class="question-step" id="stepSuccess">
                        <div class="submit-success">
                            <div style="width: 80px; height: 80px; background: rgba(16, 185, 129, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                                <i class="fas fa-heart" style="font-size: 40px; color: #10b981;"></i>
                            </div>
                            <h2 style="color: white; margin-bottom: 10px;">Thank You!</h2>
                            <p style="color: #cbd5e1;">Your feedback has been submitted successfully.<br>It helps us improve our care for you.</p>
                            <a href="patient_dashboard.php" class="btn-nav btn-next" style="display: inline-block; text-decoration: none; margin-top: 20px;">Back to Dashboard</a>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="nav-buttons" id="navButtons">
                        <button class="btn-nav btn-prev" onclick="prevStep()" id="btnPrev" style="visibility: hidden;">Back</button>
                        <button class="btn-nav btn-next" onclick="nextStep()" id="btnNext" disabled>Next Question</button>
                        <button class="btn-nav btn-next" onclick="submitFeedback()" id="btnSubmit" style="display: none; background: #10b981;">Submit Feedback</button>
                    </div>

                </div>

                <!-- Live Preview -->
                <div class="preview-card">
                    <h3 style="color: #4fc3f7; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-eye"></i> Live Summary
                    </h3>
                    
                    <div class="preview-item" id="preview1">
                        <span class="preview-label">1. Overall Experience</span>
                        <span class="preview-value" id="val1">--</span>
                    </div>

                    <div class="preview-item" id="preview2">
                        <span class="preview-label">2. Doctor Consultation</span>
                        <span class="preview-value" id="val2">--</span>
                    </div>

                    <div class="preview-item" id="preview3">
                        <span class="preview-label">3. Cleanliness</span>
                        <span class="preview-value" id="val3">--</span>
                    </div>

                    <div class="preview-item" id="preview4">
                        <span class="preview-label">4. Staff Response</span>
                        <span class="preview-value" id="val4">--</span>
                    </div>

                    <div class="preview-item" id="preview5" style="border: none;">
                        <span class="preview-label">5. Your Comments</span>
                        <span class="preview-value" id="val5" style="font-style: italic; font-size: 13px;">--</span>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 5;
        const feedbackData = {
            1: null,
            2: null,
            3: null,
            4: null,
            5: ''
        };

        function updateProgress() {
            const percent = (currentStep / totalSteps) * 100;
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressPercent').innerText = percent + '%';
            document.getElementById('progressText').innerText = `Question ${currentStep} of ${totalSteps}`;
            
            // Manage Buttons
            document.getElementById('btnPrev').style.visibility = currentStep > 1 ? 'visible' : 'hidden';
            
            if (currentStep === totalSteps) {
                document.getElementById('btnNext').style.display = 'none';
                document.getElementById('btnSubmit').style.display = 'block';
            } else {
                document.getElementById('btnNext').style.display = 'block';
                document.getElementById('btnSubmit').style.display = 'none';
            }

            // Check if current step has value to enable Next
            validateStep();
        }

        function validateStep() {
            let isValid = false;
            
            if (currentStep === 5) {
               isValid = true; // Comments optional
            } else if (feedbackData[currentStep]) {
               isValid = true;
            }

            document.getElementById('btnNext').disabled = !isValid;
        }

        function showStep(step) {
            document.querySelectorAll('.question-step').forEach(el => el.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');
            currentStep = step;
            updateProgress();
        }

        function nextStep() {
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                showStep(currentStep - 1);
            }
        }

        /* ----- Interactions ----- */
        function selectOption(step, value, displayValue) {
            feedbackData[step] = value;
            
            // Visual Selection
            const options = document.querySelectorAll(`#step${step} .option-card`);
            options.forEach(opt => opt.classList.remove('selected'));
            event.currentTarget.classList.add('selected');

            // Update Preview
            updatePreview(step, displayValue);
            
            // Enable Next
            validateStep();

            // Auto advance for smooth feel (optional, but requested "guided" feel)
            setTimeout(() => {
                if (currentStep < totalSteps) nextStep();
            }, 400); 
        }

        function rateStar(rating) {
            feedbackData[3] = rating;
            
            // Visuals
            const stars = document.querySelectorAll('#starRating i');
            stars.forEach((star, index) => {
                if (index < rating) star.classList.add('active');
                else star.classList.remove('active');
            });
            
            document.getElementById('starText').innerText = rating + '/5 Stars';
            
            // Preview
            let starsStr = '';
            for(let i=0; i<rating; i++) starsStr += '⭐';
            updatePreview(3, starsStr);
            
            validateStep();
            setTimeout(() => { nextStep(); }, 600);
        }

        function updateComment(text) {
            feedbackData[5] = text;
            updatePreview(5, text ? `"${text}"` : '--');
        }

        function updatePreview(step, content) {
            document.getElementById('val' + step).innerText = content;
            document.getElementById('preview' + step).classList.add('filled');
        }

        function submitFeedback() {
            // Disable button
            const btn = document.getElementById('btnSubmit');
            btn.disabled = true;
            btn.innerHTML = 'Submitting...';

            const formData = new FormData();
            formData.append('action', 'submit_feedback');
            formData.append('feedback_data', JSON.stringify(feedbackData));

            fetch('patient_feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                     // Hide Wizard, Show Success
                    document.getElementById('step' + currentStep).classList.remove('active');
                    document.getElementById('stepSuccess').classList.add('active');
                    
                    // Hide Buttons & Progress
                    document.getElementById('navButtons').style.display = 'none';
                    document.querySelector('.progress-container').style.display = 'none';
                } else {
                    alert('Error submitting feedback: ' + result.message);
                    btn.disabled = false;
                    btn.innerHTML = 'Submit Feedback';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                btn.disabled = false;
                btn.innerHTML = 'Submit Feedback';
            });
        }

        // Init
        // Init
        updateProgress();

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
