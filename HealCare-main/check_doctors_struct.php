<?php
include 'includes/db_connect.php';
$result = $conn->query("SHOW COLUMNS FROM doctors");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Table 'doctors' does not exist or has no columns.\n";
}
?>
