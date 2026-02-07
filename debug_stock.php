<?php
include 'includes/db_connect.php';

echo "<h2>Pharmacy Stock Check</h2>";
$sql = "SELECT * FROM pharmacy_stock ORDER BY quantity ASC LIMIT 20";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Qty</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['stock_id']}</td><td>{$row['medicine_name']}</td><td>{$row['quantity']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "No stock found.";
}
?>
