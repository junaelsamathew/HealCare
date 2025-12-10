<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}
$role = ucfirst($_SESSION['user_role']);
include 'includes/header.php'; 
?>

<div class="container" style="padding-top: 120px; padding-bottom: 50px;">
    <div class="glass-panel" style="padding: 2rem;">
        <h1>Welcome, <?php echo $role; ?>!</h1>
        <p>You have successfully logged in.</p>
        
        <div style="margin-top: 2rem;">
            <h3>Dashboard Overview</h3>
            <p>This is the placeholder for the <?php echo $role; ?> Dashboard.</p>
        </div>

        <a href="logout.php" class="btn btn-secondary" style="margin-top: 2rem; display: inline-block;">Log Out</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
