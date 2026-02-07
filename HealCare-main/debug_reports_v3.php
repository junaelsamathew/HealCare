<?php
ob_start();
include 'includes/db_connect.php';

echo "<h2>Debug Manual Reports V3</h2>";
$sql = "SELECT * FROM manual_reports ORDER BY report_id DESC LIMIT 20";
$res = $conn->query($sql);

echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>User ID</th><th>Type</th><th>Category</th><th>Title</th></tr>";
while ($row = $res->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['report_id']}</td>";
    echo "<td>{$row['user_id']}</td>";
    echo "<td>{$row['report_type']}</td>";
    echo "<td>{$row['report_category']}</td>";
    echo "<td>{$row['report_title']}</td>";
    echo "</tr>";
}
echo "</table>";

file_put_contents('debug_output_v3.html', ob_get_clean());
?>
