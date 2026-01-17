<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_GET['bill_id'])) {
    die("Invalid Request");
}

$bill_id = intval($_GET['bill_id']);

// Update Status to Pay At Counter (or just leave pending, but maybe add a note?)
// For now, let's keep it 'Pending' but maybe update method preference?
// Or we can create a specific status 'Pay at Counter' if the Enum allows.
// Let's check Schema... ENUM('Pending', 'Paid', 'Failed') usually. 
// Safest is to leave as Pending but redirect user to a "Instructions" page or back to billing with message.

header("Location: billing.php?msg=pay_at_counter");
exit();
?>
