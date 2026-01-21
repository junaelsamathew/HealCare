<?php
include 'includes/db_connect.php';

echo "<h2>Consultation Traffic Data (Last 7 Days)</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Date</th><th>Day</th><th>Appointments Count</th></tr>";

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $display_date = date('M d, Y', strtotime("-$i days"));
    $day_name = date('l', strtotime("-$i days"));
    
    $result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = '$date'");
    $count = $result ? $result->fetch_assoc()['count'] : 0;
    
    echo "<tr>";
    echo "<td>$display_date</td>";
    echo "<td>$day_name</td>";
    echo "<td><strong>$count</strong></td>";
    echo "</tr>";
}

echo "</table>";

echo "<br><h3>Total Appointments in Database:</h3>";
$total = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];
echo "<p>Total: <strong>$total</strong> appointments</p>";

echo "<br><h3>Recent Appointments:</h3>";
$recent = $conn->query("SELECT appointment_id, patient_id, doctor_id, appointment_date, status FROM appointments ORDER BY appointment_date DESC LIMIT 10");
if ($recent && $recent->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Patient ID</th><th>Doctor ID</th><th>Date</th><th>Status</th></tr>";
    while ($row = $recent->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['appointment_id']}</td>";
        echo "<td>{$row['patient_id']}</td>";
        echo "<td>{$row['doctor_id']}</td>";
        echo "<td>{$row['appointment_date']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No appointments found.</p>";
}
?>
