<?php
include 'includes/db_connect.php';

$res = $conn->query('DESCRIBE doctors');
echo "Columns in doctors table:\n";
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}

echo "\nData for June Mary Antony:\n";
$sql = "SELECT r.registration_id, r.name, u.user_id, d.* 
        FROM registrations r 
        JOIN users u ON r.registration_id = u.registration_id 
        JOIN doctors d ON u.user_id = d.user_id
        WHERE r.name LIKE '%June Mary Antony%'";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    print_r($result->fetch_assoc());
} else {
    echo "No doctor found.\n";
}
?>
