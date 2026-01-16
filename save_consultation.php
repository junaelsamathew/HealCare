<?php
session_start();
include 'includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $appt_id = $_POST['appointment_id'];
    $diagnosis = $_POST['diagnosis'];
    $treatment = $_POST['treatment'];
    $special_notes = $_POST['special_notes'];
    $prescription_text = $_POST['prescription'];
    $lab_required = isset($_POST['lab_required']) ? 1 : 0;
    $lab_test_name = $_POST['lab_test_name'] ?? '';
    $lab_category = $_POST['lab_category'] ?? '';

    $conn->begin_transaction();

    try {
        // 1. Update Appointment Status to COMPLETED
        $stmt_appt = $conn->prepare("UPDATE appointments SET status = 'Completed' WHERE appointment_id = ?");
        $stmt_appt->bind_param("i", $appt_id);
        $stmt_appt->execute();

        // 1.5 Generate Billing for Consultation (IF NOT EXISTS)
        // Check if bill exists
        $check_bill = $conn->query("SELECT bill_id FROM billing WHERE appointment_id = $appt_id AND bill_type = 'Consultation'");
        if ($check_bill->num_rows == 0) {
            // Fetch fee first
            $stmt_fee = $conn->prepare("SELECT consultation_fee FROM doctors WHERE user_id = ?");
            $stmt_fee->bind_param("i", $doctor_id);
            $stmt_fee->execute();
            $res_fee = $stmt_fee->get_result();
            $fee = 500; // Default fallback
            if ($res_fee->num_rows > 0) {
                $d_row = $res_fee->fetch_assoc();
                $fee = $d_row['consultation_fee'] ?: 500;
            }

            // Insert Bill
            $bill_type = 'Consultation';
            $bill_status = 'Pending';
            $bill_date = date('Y-m-d');
            $stmt_bill = $conn->prepare("INSERT INTO billing (patient_id, appointment_id, doctor_id, bill_type, total_amount, payment_status, bill_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_bill->bind_param("iiisdss", $patient_id, $appt_id, $doctor_id, $bill_type, $fee, $bill_status, $bill_date);
            $stmt_bill->execute();
        }

        // 2. Save Prescription if exists
        $prescription_id = null;
        if (!empty($prescription_text)) {
            $date = date('Y-m-d');
            $stmt_presc = $conn->prepare("INSERT INTO prescriptions (patient_id, doctor_id, prescription_date, medicine_details, instructions) VALUES (?, ?, ?, ?, ?)");
            $stmt_presc->bind_param("iisss", $patient_id, $doctor_id, $date, $prescription_text, $special_notes);
            $stmt_presc->execute();
            $prescription_id = $conn->insert_id;
        }

        // 3. Save Lab Order if required
        if ($lab_required) {
            $status = 'Pending';
            $stmt_lab = $conn->prepare("INSERT INTO lab_tests (patient_id, doctor_id, appointment_id, test_name, instructions, test_type, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_lab->bind_param("iiissss", $patient_id, $doctor_id, $appt_id, $lab_test_name, $special_notes, $lab_category, $status);
            $stmt_lab->execute();
        }

        // 4. Create Medical Record
        $stmt_rec = $conn->prepare("INSERT INTO medical_records (patient_id, doctor_id, appointment_id, diagnosis, treatment, prescription_id, lab_test_required, record_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Finalized')");
        $stmt_rec->bind_param("iiisssi", $patient_id, $doctor_id, $appt_id, $diagnosis, $treatment, $prescription_id, $lab_required);
        $stmt_rec->execute();

        $conn->commit();
        header("Location: doctor_dashboard.php?msg=Consultation finalized successfully!");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error saving consultation: " . $e->getMessage();
    }
}
?>
