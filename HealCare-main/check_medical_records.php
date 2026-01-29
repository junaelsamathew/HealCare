<?php
include 'includes/db_connect.php';

echo "<h2>Medical Records Data (Last 7 Days)</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Date</th><th>Day</th><th>Medical Records Count</th></tr>";

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $display_date = date('M d, Y', strtotime("-$i days"));
    $day_name = date('l', strtotime("-$i days"));
    
    $result = $conn->query("SELECT COUNT(*) as count FROM medical_records WHERE DATE(created_at) = '$date'");
    $count = $result ? $result->fetch_assoc()['count'] : 0;
    
    echo "<tr>";
    echo "<td>$display_date</td>";
    echo "<td>$day_name</td>";
    echo "<td><strong>$count</strong></td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><h3>Recent Medical Records:</h3>";
$recent = $conn->query("SELECT record_id, patient_id, doctor_id, created_at, diagnosis FROM medical_records ORDER BY created_at DESC LIMIT 10");
if ($recent && $recent->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Patient ID</th><th>Doctor ID</th><th>Created At</th><th>Diagnosis</th></tr>";
    while ($row = $recent->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['record_id']}</td>";
        echo "<td>{$row['patient_id']}</td>";
        echo "<td>{$row['doctor_id']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "<td>" . substr(htmlspecialchars($row['diagnosis']), 0, 50) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No medical records found.</p>";
}
?>
