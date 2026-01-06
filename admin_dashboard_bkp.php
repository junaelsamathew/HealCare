<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HealCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="styles/dashboard.css">
</head>

<body>
    <!-- Top Header -->
    <header class="top-header">
        <a href="index.php" class="logo-main">HEALCARE</a>
        <div class="header-info-group">
            <div class="header-info-item">
                <div class="info-icon">📞</div>
                <div class="info-details">
                    <span class="info-label">Emergency</span>
                    <span class="info-value">(+254) 717 783 146</span>
                </div>
            </div>
            <div class="header-info-item">
                <div class="info-icon">⏰</div>
                <div class="info-details">
                    <span class="info-label">Work Hour</span>
                    <span class="info-value">09:00 - 20:00 Everyday</span>
                </div>
            </div>
            <div class="header-info-item">
                <div class="info-icon">📍</div>
                <div class="info-details">
                    <span class="info-label">Location</span>
                    <span class="info-value">0123 Some Place</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Secondary Header -->
    <div class="secondary-header">
        <div class="brand-section">
            <span class="brand-icon">+</span>
            <span class="brand-name">HealCare</span>
        </div>
        <div class="user-controls">
            <span class="user-greeting">Administrator</span>
            <a href="logout.php" class="btn-logout">Log Out</a>
        </div>
    </div>

    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <a href="#" class="nav-link active">Overview</a>
            <a href="#" class="nav-link">User Management</a>
            <a href="#" class="nav-link">Departments</a>
            <a href="#" class="nav-link">Financial Reports</a>
            <a href="#" class="nav-link">System Settings</a>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <section class="page-title-section">
                <h1 class="page-title">Admin Control Panel</h1>
                <p class="page-subtitle">System-wide monitoring and administration.</p>
            </section>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">542</div>
                    <div class="stat-label">Total Patients</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">85</div>
                    <div class="stat-label">Total Doctors</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">$125k</div>
                    <div class="stat-label">Monthly Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">Active</div>
                    <div class="stat-label">System Status</div>
                </div>
            </div>

            <div class="content-block">
                <div class="block-header">
                    <h2 class="block-title">System Alerts & Logs</h2>
                </div>
                <div class="list-container">
                    <div class="alert-row">
                        <span class="alert-text success">[Success] Database Backup Completed</span>
                        <span class="alert-time">10 mins ago</span>
                    </div>
                    <div class="alert-row">
                        <span class="alert-text warning">[Warning] High Server Load</span>
                        <span class="alert-time">1 hour ago</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>