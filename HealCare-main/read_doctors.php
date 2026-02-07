<?php
include 'includes/db_connect.php';

$dept = isset($_GET['dept']) ? $_GET['dept'] : '';
$sql = "SELECT u.user_id, r.name, d.department 
        FROM doctors d 
        JOIN users u ON d.user_id = u.user_id 
        JOIN registrations r ON u.registration_id = r.registration_id";

if (!empty($dept)) {
    $safe_dept = mysqli_real_escape_string($conn, $dept);
    $sql .= " WHERE d.department = '$safe_dept'";
}

$result = $conn->query($sql);
$doctors = [];
while($row = $result->fetch_assoc()) {
    $doctors[] = $row;
}

header('Content-Type: application/json');
echo json_encode($doctors);
?>
