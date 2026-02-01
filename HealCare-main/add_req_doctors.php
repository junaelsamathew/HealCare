<?php
include 'includes/db_connect.php';

$new_doctors = [
    ['name' => 'Dr. Johny Mathew', 'email' => 'johnymathew56@gmail.com', 'dept' => 'General Medicine / Cardiovascular', 'sex' => 'Male'],
    ['name' => 'Dr. Jaisan Mathew', 'email' => 'jaisanmathew43@gmail.com', 'dept' => 'Orthopedics (Bones)', 'sex' => 'Male'],
    ['name' => 'Dr. Jincy Mathew', 'email' => 'jincymathew72@gmail.com', 'dept' => 'Gynecology', 'sex' => 'Female'],
    ['name' => 'Dr. Cicily Mathew', 'email' => 'cicilymathew56@gmail.com', 'dept' => 'ENT', 'sex' => 'Female'],
    ['name' => 'Dr. Pavithra Binu', 'email' => 'pavithrabinu657@gmail.com', 'dept' => 'Ophthalmology', 'sex' => 'Female'],
    ['name' => 'Dr. Amala Ann Joseph', 'email' => 'amalaannjoseph@gmail.com', 'dept' => 'Dermatology', 'sex' => 'Female']
];

$conn->begin_transaction();

try {
    foreach ($new_doctors as $doc) {
        // Check if email exists
        $check = $conn->query("SELECT * FROM registrations WHERE email = '" . $doc['email'] . "'");
        if ($check->num_rows > 0) {
            echo "Skipping " . $doc['email'] . " (Already exists)\n";
            continue;
        }

        // 1. Insert Registration
        $pass = password_hash('Pass1234', PASSWORD_DEFAULT);
        $sql_reg = "INSERT INTO registrations (name, email, phone, password, user_type, status, staff_type) 
                    VALUES ('" . $doc['name'] . "', '" . $doc['email'] . "', '9999999999', '$pass', 'doctor', 'Approved', 'Doctor')";
        $conn->query($sql_reg);
        $reg_id = $conn->insert_id;

        // 2. Insert User
        $username = explode('@', $doc['email'])[0];
        $sql_user = "INSERT INTO users (registration_id, username, email, password, role, status)
                     VALUES ($reg_id, '$username', '" . $doc['email'] . "', '$pass', 'doctor', 'Active')";
        $conn->query($sql_user);
        $user_id = $conn->insert_id;

        // 3. Insert Doctor Profile
        $sql_doc = "INSERT INTO doctors (user_id, specialization, qualification, experience, department, designation)
                    VALUES ($user_id, '" . $doc['dept'] . "', 'MBBS, MD', '5 Years', '" . $doc['dept'] . "', 'Senior Consultant')";
        $conn->query($sql_doc);

        echo "Added " . $doc['name'] . "\n";
    }
    $conn->commit();
    echo "All doctors processed.\n";
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}
?>
