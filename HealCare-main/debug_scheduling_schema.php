<?php
include 'includes/db_connect.php';

echo "--- doctors table ---\n";
$res1 = $conn->query("DESCRIBE doctors");
if ($res1) {
    while($row = $res1->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}

echo "\n--- checking for schedule tables ---\n";
$tables = ['doctor_schedules', 'schedules', 'availability'];
foreach ($tables as $table) {
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    if ($res->num_rows > 0) {
        echo "Table '$table' exists.\n";
        $res2 = $conn->query("DESCRIBE $table");
        while($row = $res2->fetch_assoc()) {
            echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "Table '$table' does not exist.\n";
    }
}

echo "\n--- distinct departments ---\n";
$res3 = $conn->query("SELECT DISTINCT department FROM doctors");
if ($res3) {
    while($row = $res3->fetch_assoc()) {
        echo $row['department'] . "\n";
    }
}
?>
