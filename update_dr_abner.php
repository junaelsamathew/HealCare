<?php
include 'includes/db_connect.php';

$user_id = 35;
$dept = 'ENT';
$spec = 'Otolaryngologist';
$desig = 'Senior Consultant';

// Check if record exists
$res = $conn->query("SELECT * FROM doctors WHERE user_id = $user_id");
if ($res->num_rows > 0) {
    $sql = "UPDATE doctors SET department = ?, specialization = ?, designation = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $dept, $spec, $desig, $user_id);
    if ($stmt->execute()) {
        echo "Doctor profile updated successfully.";
    } else {
        echo "Error updating profile: " . $conn->error;
    }
} else {
    // Insert if not exists
    $sql = "INSERT INTO doctors (user_id, department, specialization, designation) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $user_id, $dept, $spec, $desig);
    if ($stmt->execute()) {
        echo "Doctor profile created successfully.";
    } else {
        echo "Error creating profile: " . $conn->error;
    }
}
?>
