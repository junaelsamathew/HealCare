<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'doctor') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $test_type = mysqli_real_escape_string($conn, $_POST['test_type']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority'] ?? 'Normal');
    $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? '');
    $doctor_id = $_SESSION['user_id'];
    
    // Validate inputs
    if (empty($patient_id) || empty($test_type)) {
        die(json_encode(['success' => false, 'message' => 'Patient and Test Type are required']));
    }
    
    // Determine lab category based on test type
    $category_name = 'Blood / Pathology Lab'; // Default
    
    if (stripos($test_type, 'X-Ray') !== false || 
        stripos($test_type, 'MRI') !== false || 
        stripos($test_type, 'CT') !== false || 
        stripos($test_type, 'Scan') !== false) {
        $category_name = 'X-Ray / Imaging Lab';
    } elseif (stripos($test_type, 'Ultrasound') !== false || 
              stripos($test_type, 'Doppler') !== false) {
        $category_name = 'Ultrasound / Diagnostic Lab';
    } elseif (stripos($test_type, 'ECG') !== false || 
              stripos($test_type, 'Hearing') !== false || 
              stripos($test_type, 'Eye') !== false) {
        $category_name = 'Diagnostic Lab';
    }
    
    // Get category_id from lab_categories table
    $cat_query = $conn->prepare("SELECT category_id FROM lab_categories WHERE category_name = ? LIMIT 1");
    $cat_query->bind_param("s", $category_name);
    $cat_query->execute();
    $cat_result = $cat_query->get_result();
    
    if ($cat_result->num_rows == 0) {
        // Fallback to ID 1 (Blood/Pathology) if category not found
        $category_id = 1;
    } else {
        $category_id = $cat_result->fetch_assoc()['category_id'];
    }
    
    // Insert lab test request
    $sql = "INSERT INTO lab_tests 
            (patient_id, doctor_id, category_id, test_name, priority, instructions, status, test_date, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending', CURDATE(), NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiisss", $patient_id, $doctor_id, $category_id, $test_type, $priority, $reason);
    
    if ($stmt->execute()) {
        $test_id = $conn->insert_id;
        
        // Get patient name for response
        $pat_q = $conn->query("SELECT r.name FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE u.user_id = $patient_id");
        $pat_name = $pat_q->fetch_assoc()['name'] ?? 'Patient';
        
        echo json_encode([
            'success' => true, 
            'message' => "Lab test request sent successfully! Test ID: #$test_id",
            'test_id' => $test_id,
            'patient_name' => $pat_name,
            'test_type' => $test_type,
            'category' => $category_name
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    
    $stmt->close();
    $conn->close();
}
?>
