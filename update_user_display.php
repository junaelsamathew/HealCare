<?php
include 'includes/db_connect.php';

echo "Updating User Display Data...\n";

// 1. Get all doctors and staff
$res = $conn->query("SELECT u.user_id, u.role, r.registration_id, r.app_id 
                     FROM users u 
                     JOIN registrations r ON u.registration_id = r.registration_id 
                     WHERE u.role IN ('doctor', 'staff')");

$conn->begin_transaction();

try {
    $i = 1001;
    while ($row = $res->fetch_assoc()) {
        $reg_id = $row['registration_id'];
        $uid = $row['user_id'];
        
        // Generate App ID if not already a real one (ignore placeholder 'Manual')
        if (empty($row['app_id']) || $row['app_id'] == 'Manual' || $row['app_id'] == '0') {
            $prefix = ($row['role'] == 'doctor') ? 'DOC' : 'STF';
            $new_app_id = "APP-" . $prefix . "-" . $i;
            $conn->query("UPDATE registrations SET app_id = '$new_app_id' WHERE registration_id = $reg_id");
            echo "Assigned $new_app_id to Registration #$reg_id\n";
            $i += rand(5, 50);
        }

        // 2. Randomize created_at date (Sep 2025 to Jan 2026)
        $random_days = rand(0, 120); // up to 4 months ago
        $random_date = date('Y-m-d H:i:s', strtotime("-" . $random_days . " days - " . rand(0, 23) . " hours"));
        $conn->query("UPDATE users SET created_at = '$random_date' WHERE user_id = $uid");
    }

    // Also randomize patient dates so they don't look identical
    $patients = $conn->query("SELECT user_id FROM users WHERE role = 'patient'");
    while ($p = $patients->fetch_assoc()) {
        $uid = $p['user_id'];
        $random_days = rand(0, 30);
        $random_date = date('Y-m-d H:i:s', strtotime("-" . $random_days . " days - " . rand(0, 23) . " hours"));
        $conn->query("UPDATE users SET created_at = '$random_date' WHERE user_id = $uid");
    }

    $conn->commit();
    echo "Update complete. All doctors/staff have App IDs and varied registration dates.\n";
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage() . "\n";
}
?>
