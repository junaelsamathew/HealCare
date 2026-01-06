<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HealCare - A Great Place to Receive Care. Caring for you, digitally and compassionately. Health made simple.">
    <title>HealCare - A Great Place to Receive Care</title>
    <link rel="stylesheet" href="styles/landing.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-content">
                <a href="index.php" class="logo">
                    <span class="logo-icon">+</span> HEALCARE
                </a>
                <div class="top-info">
                    <div class="info-item">
                        <div class="info-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </div>
                        <div class="info-text">
                            <span class="info-label">EMERGENCY</span>
                            <span class="info-value">(+254) 717 783 146</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 6v6l4 2"/>
                            </svg>
                        </div>
                        <div class="info-text">
                            <span class="info-label">WORK HOUR</span>
                            <span class="info-value">09:00 - 20:00 Everyday</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </div>
                        <div class="info-text">
                            <span class="info-label">LOCATION</span>
                            <span class="info-value">Kanjirapally, Kottayam</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="main-nav">
        <div class="container">
            <div class="nav-content">
                <ul class="nav-links">
                    <li><a href="index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="about.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : ''; ?>">About Us</a></li>
                    <li><a href="services.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'services.php') ? 'active' : ''; ?>">Services</a></li>
                    <li><a href="find_doctor.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'find_doctor.php') ? 'active' : ''; ?>">Find a Doctor</a></li>
                    <li><a href="contact.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : ''; ?>">Contact</a></li>
                    <li><a href="apply.php" style="color: #3b82f6; font-weight: 700;" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'apply.php') ? 'active' : ''; ?>">Join Our Team</a></li>
                </ul>
                <a href="login.php" class="btn-login">LOGIN/SIGNUP</a>
            </div>
        </div>
    </nav>
