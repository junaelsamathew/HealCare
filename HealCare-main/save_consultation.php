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

    // Server-side Gibberish Detection
    function is_gibberish($str) {
        if (empty($str)) return false;
        $cleanStr = strtolower(preg_replace('/[^a-z]/', '', $str));
        if (strlen($cleanStr) === 0) return false;

        // Vowel Check
        $words = explode(' ', strtolower($str));
        foreach($words as $word) {
            $cw = preg_replace('/[^a-z]/', '', $word);
            if (strlen($cw) > 3 && !preg_match('/[aeiouy]/', $cw)) {
                return true; 
            }
        }

        // Repeated Chars
        if (preg_match('/(.)\1{4,}/', $cleanStr)) return true;

        // Keyboard Patterns
        $patterns = ['qwerty', 'asdfgh', 'zxcvbn', 'qazwsx', 'edcrfv', '123456'];
        foreach($patterns as $p) {
            if (strpos($cleanStr, $p) !== false) return true;
        }

        // Entropy
        $unique_chars = count(count_chars($cleanStr, 1));
        $ratio = $unique_chars / strlen($cleanStr);
        if (strlen($cleanStr) > 8 && $ratio > 0.7 && strpos($str, ' ') === false) return true;

        return false;
    }

    if (is_gibberish($diagnosis) || is_gibberish($treatment) || is_gibberish($special_notes) || is_gibberish($prescription_text)) {
        header("Location: doctor_dashboard.php?patient_id=$patient_id&appt_id=$appt_id&error=Invalid text detected in one of the fields. Please provide meaningful medical details.");
        exit();
    }



    $conn->begin_transaction();

    try {
        // 1. Update Appointment Status
        $new_status = $lab_required ? 'Pending Lab' : 'Completed';
        $stmt_appt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
        $stmt_appt->bind_param("si", $new_status, $appt_id);
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
            $pay_status = 'Pending';
            
            // Find Category ID
            $cat_stmt = $conn->prepare("SELECT category_id FROM lab_categories WHERE category_name = ?");
            $cat_stmt->bind_param("s", $lab_category);
            $cat_stmt->execute();
            $cat_res = $cat_stmt->get_result();
            $cat_id = ($cat_row = $cat_res->fetch_assoc()) ? $cat_row['category_id'] : 0;
            


            // Create Lab Request
            $stmt_lab = $conn->prepare("INSERT INTO lab_tests (patient_id, doctor_id, appointment_id, category_id, test_name, instructions, test_type, status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_lab->bind_param("iiiisssss", $patient_id, $doctor_id, $appt_id, $cat_id, $lab_test_name, $special_notes, $lab_category, $status, $pay_status);
            $stmt_lab->execute();
        }

        // 4. Create Medical Record
        $stmt_rec = $conn->prepare("INSERT INTO medical_records (patient_id, doctor_id, appointment_id, diagnosis, treatment, prescription_id, lab_test_required, record_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Finalized')");
        $stmt_rec->bind_param("iiisssi", $patient_id, $doctor_id, $appt_id, $diagnosis, $treatment, $prescription_id, $lab_required);
        $stmt_rec->execute();

        // 5. Create Admission Request if recommended
        if (isset($_POST['admit_patient']) && $_POST['admit_patient'] == '1') {
            $ward_type = $_POST['ward_type_req'];
            $reason = $_POST['admission_reason'];
            $req_date = date('Y-m-d H:i:s');
            
            // Insert with Pending status. admission_date is NULL until room assigned.
            // If admission_date is NOT NULL in DB, we might face error. 
            // We will attempt NULL, if it fails, we might need to fix schema on fly or use NOW().
            // Ideally Pending admission has no start date yet.
            
            $stmt_adm = $conn->prepare("INSERT INTO admissions (patient_id, doctor_id, status, ward_type_req, reason, request_date) VALUES (?, ?, 'Pending', ?, ?, ?)");
            $stmt_adm->bind_param("iisss", $patient_id, $doctor_id, $ward_type, $reason, $req_date);
            if (!$stmt_adm->execute()) {
                 // Fallback if admission_date is required
                 // $stmt_adm = $conn->prepare("INSERT INTO admissions (patient_id, doctor_id, admission_date, status, ward_type_req, reason, request_date) VALUES (?, ?, NOW(), 'Pending', ?, ?, ?)");
                 // $stmt_adm->bind_param("iisss", $patient_id, $doctor_id, $ward_type, $reason, $req_date);
                 // $stmt_adm->execute();
                 // But strictly speaking, pending shouldn't have date.
                 throw new Exception("Error creating admission request: " . $conn->error);
            }
        }

        $conn->commit();
        header("Location: doctor_dashboard.php?msg=Consultation finalized successfully!");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error saving consultation: " . $e->getMessage();
    }
}
?>
