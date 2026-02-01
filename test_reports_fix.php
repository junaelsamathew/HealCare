<?php
<<<<<<< HEAD
session_start();
include 'includes/db_connect.php';

echo "Session Role: " . ($_SESSION['user_role'] ?? 'Not set') . "<br>";
echo "Session User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";

$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id > 0) {
    $c = $conn->query("SELECT 'nurse' as t FROM nurses WHERE user_id=$user_id 
                      UNION SELECT 'lab_staff' FROM lab_staff WHERE user_id=$user_id 
                      UNION SELECT 'pharmacist' FROM pharmacists WHERE user_id=$user_id 
                      UNION SELECT 'receptionist' FROM receptionists WHERE user_id=$user_id 
                      UNION SELECT 'canteen_staff' FROM canteen_staff WHERE user_id=$user_id");
    if($c) {
        while($r = $c->fetch_assoc()) {
            echo "Found Staff Type: " . $r['t'] . "<br>";
        }
    } else {
        echo "Query failed: " . $conn->error . "<br>";
    }
}
=======
include 'includes/db_connect.php';

echo "Testing the manual_reports table queries...\n\n";

// Test the exact query that was failing
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM manual_reports WHERE status = 'Pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✓ SUCCESS: Query executed successfully!\n";
        echo "  Pending reports count: " . $row['count'] . "\n\n";
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n\n";
}

// Test all the queries from admin_dashboard.php line 2682-2685
echo "Testing all report statistics queries:\n";
echo "----------------------------------------\n";

try {
    $total_reports = $conn->query("SELECT COUNT(*) as count FROM manual_reports")->fetch_assoc()['count'];
    echo "✓ Total reports: $total_reports\n";
    
    $pending_reports = $conn->query("SELECT COUNT(*) as count FROM manual_reports WHERE status = 'Pending'")->fetch_assoc()['count'];
    echo "✓ Pending reports: $pending_reports\n";
    
    $submitted_reports = $conn->query("SELECT COUNT(*) as count FROM manual_reports WHERE status = 'Submitted'")->fetch_assoc()['count'];
    echo "✓ Submitted reports: $submitted_reports\n";
    
    $this_month_reports = $conn->query("SELECT COUNT(*) as count FROM manual_reports WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetch_assoc()['count'];
    echo "✓ This month reports: $this_month_reports\n";
    
    echo "\n✓ All queries executed successfully!\n";
    echo "\nThe admin dashboard should now work without errors.\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}

$conn->close();
>>>>>>> df85a51ef41de3403fc0cd2d4fca911613970299
?>
