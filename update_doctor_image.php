<?php
include 'includes/db_connect.php';

$name = "June Mary Antony";
$image_path = "images/dr_june_mary_antony.png";

// Update profile photo in registrations table
$sql = "UPDATE registrations SET profile_photo = ? WHERE name LIKE ?";
$stmt = $conn->prepare($sql);
$param_name = "%" . $name . "%";
$stmt->bind_param("ss", $image_path, $param_name);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "Successfully updated profile photo for doctor '$name' to '$image_path'.";
    } else {
        echo "No records were updated. Check if the doctor name matches exactly.";
    }
} else {
    echo "Error updating record: " . $conn->error;
}
?>
