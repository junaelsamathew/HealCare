<?php
session_start();
header('Content-Type: application/json');

// --- RAZORPAY CONFIG ---
$key_id = "rzp_test_S5FSFS5I38bWXy";
$key_secret = "2ISLOGjYRAekJBSbyBEiJt6V";
// -----------------------

// 1. Get Data from Frontend
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['amount']) || !isset($input['order_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing amount or order_id']);
    exit;
}

$amount = $input['amount']; // Amount in INR
$receipt = "canteen_" . $input['order_id'];

// 2. Create Order via Razorpay API (Direct cURL Request)
$api_url = "https://api.razorpay.com/v1/orders";
$data = [
    'amount' => $amount * 100, // Convert to Paise
    'currency' => 'INR',
    'receipt' => $receipt,
    'payment_capture' => 1     // Auto capture payment
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $key_id . ":" . $key_secret); // Basic Auth
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

// Fix for Local XAMPP SSL Issues
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);

if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'cURL Error', 'details' => curl_error($ch)]);
    curl_close($ch);
    exit;
}

$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_status === 200) {
    // Return the Order ID and Key ID to frontend
    $orderData = json_decode($response, true);
    $orderData['key_id'] = $key_id; // Pass key to frontend for convenience
    echo json_encode($orderData);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create Razorpay order', 'details' => $response]);
}
?>
