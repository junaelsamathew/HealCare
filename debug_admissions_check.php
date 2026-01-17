<?php
include 'includes/db_connect.php';
echo "<h2>Admissions Table Dump</h2>";
$res = $conn->query("SELECT * FROM admissions");
if ($res->num_rows > 0) {
    echo "<table border='1'><tr>";
    while ($field = $res->fetch_field()) {
        echo "<th>{$field->name}</th>";
    }
    echo "</tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $key => $val) {
            echo "<td>$val</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No admissions found.";
}

echo "<h2>Current Doctor Session</h2>";
session_start();
print_r($_SESSION);
?>
