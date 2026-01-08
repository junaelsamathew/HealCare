<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'doctor') {
    die("Access Denied");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_id = intval($_POST['patient_id']);
    $doctor_id = $_SESSION['user_id'];
    $appointment_id = intval($_POST['appointment_id']);
    $diagnosis = $_POST['diagnosis'];
    $treatment_notes = $_POST['treatment'];
    $special_notes = $_POST['special_notes'];
    $prescription_text = $_POST['prescription'];
    
    $lab_required = isset($_POST['lab_required']) ? 'Yes' : 'No';
    $lab_test_name = $_POST['lab_test_name'] ?? '';
    $lab_category = $_POST['lab_category'] ?? '';

    $conn->begin_transaction();

    try {
        // 1. Insert Medical Record
        $stmt = $conn->prepare("INSERT INTO medical_records (patient_id, doctor_id, appointment_id, diagnosis, treatment, special_notes, lab_test_required, record_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Closed')");
        $stmt->bind_param("iiissss", $patient_id, $doctor_id, $appointment_id, $diagnosis, $treatment_notes, $special_notes, $lab_required);
        $stmt->execute();
        $record_id = $conn->insert_id;

        // 2. Insert Prescription if text exists
        if (!empty($prescription_text)) {
            $stmt_presc = $conn->prepare("INSERT INTO prescriptions (patient_id, doctor_id, appointment_id, prescription_date, medicine_details, instructions) VALUES (?, ?, ?, CURRENT_DATE, ?, ?)");
            $stmt_presc->bind_param("iiiss", $patient_id, $doctor_id, $appointment_id, $prescription_text, $special_notes);
            $stmt_presc->execute();
        }

        // 3. Insert Lab Order if required
        if ($lab_required == 'Yes' && !empty($lab_test_name)) {
            $stmt_lab = $conn->prepare("INSERT INTO lab_orders (patient_id, doctor_id, appointment_id, lab_category, test_name, instructions) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_lab->bind_param("iiisss", $patient_id, $doctor_id, $appointment_id, $lab_category, $lab_test_name, $special_notes);
            $stmt_lab->execute();
        }

        // 4. Update Appointment Status
        if ($appointment_id > 0) {
            $stmt_upd = $conn->prepare("UPDATE appointments SET status = 'Completed' WHERE appointment_id = ?");
            $stmt_upd->bind_param("i", $appointment_id);
            $stmt_upd->execute();
        }

        // 5. Generate Bill for Consultation
        $stmt_fee = $conn->prepare("SELECT consultation_fee FROM doctors WHERE user_id = ?");
        $stmt_fee->bind_param("i", $doctor_id);
        $stmt_fee->execute();
        $fee_res = $stmt_fee->get_result();
        $doc_fee = 150.00; // default
        if ($fee_res->num_rows > 0) {
            $doc_fee = $fee_res->fetch_assoc()['consultation_fee'];
        }

        $stmt_bill = $conn->prepare("INSERT INTO billing (patient_id, appointment_id, doctor_id, bill_type, total_amount, payment_status, bill_date) VALUES (?, ?, ?, 'Consultation', ?, 'Pending', CURRENT_DATE)");
        $stmt_bill->bind_param("iiid", $patient_id, $appointment_id, $doctor_id, $doc_fee);
        $stmt_bill->execute();

        $conn->commit();
        header("Location: doctor_dashboard.php?msg=consultation_finalized");
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error saving consultation: " . $e->getMessage();
    }
}
?>
