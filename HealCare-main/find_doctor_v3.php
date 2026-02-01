<?php
include 'includes/db_connect.php';

function listColumns($table, $conn) {
    echo "Columns for $table: ";
    $res = $conn->query("SHOW COLUMNS FROM $table");
    $cols = [];
    while($row = $res->fetch_assoc()) {
        $cols[] = $row['Field'];
    }
    echo implode(", ", $cols) . "\n";
}

listColumns('registrations', $conn);
listColumns('users', $conn);
listColumns('doctors', $conn);

$res = $conn->query("SELECT r.registration_id, r.name, u.user_id, u.role, d.department, d.specialization 
                    FROM registrations r 
                    JOIN users u ON r.registration_id = u.registration_id 
                    LEFT JOIN doctors d ON u.user_id = d.user_id 
                    WHERE r.name LIKE '%Abner Sam Jose%'");

while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
