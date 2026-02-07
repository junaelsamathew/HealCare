<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'doctor') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $diagnosis = mysqli_real_escape_string($conn, $_POST['diagnosis']);
    $instructions = mysqli_real_escape_string($conn, $_POST['instructions'] ?? '');
    
    // Parse medicines from JSON
    $medicines = json_decode($_POST['medicines'] ?? '[]', true);
    $doctor_id = $_SESSION['user_id'];
    
    // Validate inputs
    if (empty($patient_id) || empty($diagnosis) || empty($medicines) || empty($instructions)) {
        die(json_encode(['success' => false, 'message' => 'All fields including Patient, Diagnosis, Medicines, and Instructions are required']));
    }
    
    // Validate each medicine detail
    foreach ($medicines as $med) {
        if (empty($med['name']) || empty($med['dosage']) || empty($med['duration'])) {
            die(json_encode(['success' => false, 'message' => 'Incomplete medicine details. Name, Dosage, and Duration are required for all medications.']));
        }
    }
    
    // Format medicines into a structured string
    $medicine_details = '';
    $dosage_summary = '';
    $duration_summary = '';
    
    foreach ($medicines as $index => $med) {
        if (empty($med['name'])) continue;
        
        $med_name = $med['name'];
        $med_dosage = $med['dosage'] ?? '';
        $med_frequency = $med['frequency'] ?? '1-0-1';
        $med_duration = $med['duration'] ?? '';
        
        if ($index > 0) {
            $medicine_details .= ', ';
            $dosage_summary .= ', ';
            $duration_summary .= ', ';
        }
        
        $medicine_details .= "$med_name ($med_dosage) - $med_frequency";
        $dosage_summary .= "$med_dosage $med_frequency";
        $duration_summary .= $med_duration;
    }
    
    // Insert prescription
    $sql = "INSERT INTO prescriptions 
            (patient_id, doctor_id, diagnosis, prescription_date, medicine_details, dosage, duration, instructions, status) 
            VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, 'Active')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisssss", $patient_id, $doctor_id, $diagnosis, $medicine_details, $dosage_summary, $duration_summary, $instructions);
    
    if ($stmt->execute()) {
        $prescription_id = $conn->insert_id;
        
        // Get patient name for response
        $pat_q = $conn->query("SELECT r.name FROM users u JOIN registrations r ON u.registration_id = r.registration_id WHERE u.user_id = $patient_id");
        $pat_name = $pat_q->fetch_assoc()['name'] ?? 'Patient';
        
        // Insert notification for patient
        $notif_title = "New Prescription Issued";
        $notif_msg = "Dr. " . ($_SESSION['full_name'] ?? $_SESSION['username']) . " has issued a new prescription for your diagnosis: $diagnosis.";
        $notif_url = "prescriptions.php";
        
        $notif_sql = "INSERT INTO notifications (user_id, category, icon, color, title, message, url, priority) 
                      VALUES (?, 'Medical', 'fas fa-file-prescription', '#10b981', ?, ?, ?, 'High')";
        $stmt_notif = $conn->prepare($notif_sql);
        $stmt_notif->bind_param("isss", $patient_id, $notif_title, $notif_msg, $notif_url);
        $stmt_notif->execute();

        echo json_encode([
            'success' => true,
            'message' => "Prescription created successfully! Prescription ID: #$prescription_id\n\nThis prescription is now visible on the patient's dashboard.",
            'prescription_id' => $prescription_id,
            'patient_name' => $pat_name,
            'diagnosis' => $diagnosis
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    
    $stmt->close();
    $conn->close();
}
?>
