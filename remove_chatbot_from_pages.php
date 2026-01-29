<?php
/**
 * Script to remove chatbot widget from all pages except index.php and patient_dashboard.php
 */

$files_to_clean = [
    'signup.php',
    'services.php',
    'place_order_details.php',
    'pharmacy.php',
    'prescriptions.php',
    'payment_gateway.php',
    'patient_profile.php',
    'my_orders.php',
    'patient_lab_results.php',
    'login.php',
    'my_appointments.php',
    'medical_records.php',
    'home_care.php',
    'health_packages.php',
    'find_doctor.php',
    'emergency.php',
    'doctor_prescriptions.php',
    'doctor_patient_history.php',
    'doctor_settings.php',
    'doctor_patient_profile.php',
    'doctor_patients.php',
    'doctor_leave.php',
    'doctor_inpatient_chart.php',
    'diagnostic_center.php',
    'doctor_lab_orders.php',
    'doctor_discharge.php',
    'doctor_appointments.php',
    'doctor_dashboard.php',
    'community_clinics.php',
    'contact.php',
    'cart.php',
    'canteen_payment.php',
    'canteen.php',
    'book_appointment.php',
    'billing.php',
    'appointment_form.php'
];

$removed_count = 0;
$errors = [];

foreach ($files_to_clean as $file) {
    $filepath = __DIR__ . '/' . $file;
    
    if (!file_exists($filepath)) {
        $errors[] = "File not found: $file";
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // Pattern to match the chatbot widget include (with variations)
    $patterns = [
        "/\s*<!-- Chatbot Widget -->\s*\n\s*<\?php include 'includes\/chatbot_widget\.php'; \?>\s*\n/",
        "/\s*<\?php include 'includes\/chatbot_widget\.php'; \?>\s*\n/",
        "/\n\s*<!-- Chatbot Widget -->\s*\n\s*<\?php include 'includes\/chatbot_widget\.php'; \?>/",
    ];
    
    $original_content = $content;
    
    foreach ($patterns as $pattern) {
        $content = preg_replace($pattern, '', $content);
    }
    
    if ($content !== $original_content) {
        file_put_contents($filepath, $content);
        $removed_count++;
        echo "✓ Removed chatbot from: $file\n";
    } else {
        echo "- No chatbot found in: $file\n";
    }
}

echo "\n=================================\n";
echo "Summary:\n";
echo "Files processed: " . count($files_to_clean) . "\n";
echo "Chatbot removed from: $removed_count files\n";
echo "Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\nChatbot is now only active on:\n";
echo "  ✓ index.php\n";
echo "  ✓ patient_dashboard.php\n";
echo "=================================\n";
?>
