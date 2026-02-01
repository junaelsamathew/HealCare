<?php
// HealCare Chatbot Backend Logic

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = isset($input['message']) ? strtolower(trim($input['message'])) : '';

if (empty($userMessage)) {
    echo json_encode(['status' => 'success', 'reply' => 'I am listening. How can I help you today?']);
    exit;
}

// Function to generate response based on keywords
function getBotResponse($msg) {
    // 1. Emergency Check
    $emergency_words = ['emergency', 'chest pain', 'heart attack', 'bleeding', 'unconscious', 'breathing', 'severe', 'urgent', 'ambulance', 'stroke', 'dying'];
    foreach ($emergency_words as $word) {
        if (strpos($msg, $word) !== false) {
            return "⚠️ **EMERGENCY ALERT**: If this is a medical emergency, please call **911** or your local emergency number immediately. \n\nHealCare Emergency Hotline: **(+254) 717 783 146**.\n\nDo not rely on this chat for critical situations.";
        }
    }

    // 2. Greetings
    if (preg_match('/\b(hi|hello|hey|good morning|good evening)\b/', $msg)) {
        return "Hello! I am HealCare Assistant. I can help you with appointments, hospital services, and general guidance. How can I assist you today?";
    }

    // 3. Appointments / Booking
    if (preg_match('/\b(book|appointment|schedule|visit|doctor)\b/', $msg)) {
        return "To book an appointment:\n1. Go to the **'Book Appointment'** section in your dashboard.\n2. Select a department and doctor.\n3. Choose your preferred time slot.\n\n<a href='book_appointment.php' class='chat-link'>Click here to Book Now</a>";
    }

    // 4. Video Consultation
    if (preg_match('/\b(video|online|consultation|virtual|remote)\b/', $msg)) {
        return "We offer secure Video Consultations.\nWhen booking an appointment, select **'Online Video Consultation'** as the mode. Once approved, you will see a 'Join Video Call' button in your 'My Appointments' page at the scheduled time.";
    }

    // 5. Medical Symptoms (Disclaimer Required)
    $symptoms = ['fever', 'headache', 'pain', 'cough', 'cold', 'flu', 'stomach', 'rash', 'allergy', 'dizzy', 'vomit', 'nausea', 'hurt', 'sick'];
    foreach ($symptoms as $sym) {
        if (strpos($msg, $sym) !== false) {
            return "I am not a doctor, but I can guide you.\n\nSince you are mentioning symptoms like **$sym**, it is best to consult a specialist for a proper evaluation.\n\nWould you like to book an appointment with a General Physician? <a href='book_appointment.php?dept=General Medicine' class='chat-link'>Book General Medicine</a>";
        }
    }

    // 6. Lab Reports
    if (preg_match('/\b(lab|report|result|test|blood)\b/', $msg)) {
        return "You can view and download your Lab Reports from the **'Lab Reports'** section.\n\n<a href='patient_lab_results.php' class='chat-link'>View My Lab Reports</a>";
    }

    // 7. Prescriptions
    if (preg_match('/\b(prescription|medicine|drug|pharmacy|medication)\b/', $msg)) {
        return "Your doctor's prescriptions are available in the **'Prescriptions'** tab. You can download them or order medicines from our pharmacy.\n\n<a href='prescriptions.php' class='chat-link'>Go to Prescriptions</a>";
    }

    // 8. Canteen / Food
    if (preg_match('/\b(food|canteen|eat|diet|hungry|order)\b/', $msg)) {
        return "You can order nutritious meals directly to your room using our **Hospital Canteen** service.\n\n<a href='canteen.php' class='chat-link'>Order Food</a>";
    }

    // 9. Contact / Location
    if (preg_match('/\b(contact|phone|email|address|location|where)\b/', $msg)) {
        return "🏥 **HealCare Hospital**\n📍 Kanjirapally, Kottayam\n📞 Phone: (+254) 717 783 146\n📧 Email: support@healcare.com\n⏰ Open 24/7 for Emergencies.";
    }

    // 10. Default Fallback
    return "I'm not sure I understood that correctly. I can help with:\n- Booking Appointments\n- accessing Lab Reports\n- Video Consultations\n- Hospital Services\n\nPlease try rephrasing your question.";
}

$response = getBotResponse($userMessage);
echo json_encode(['status' => 'success', 'reply' => $response]);
?>
