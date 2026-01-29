<?php
$conn = new mysqli("127.0.0.1", "root", "", "HealCare", 3306);
$tables = ['users', 'registrations', 'lab_staff', 'nurses'];
foreach($tables as $t) {
    echo "TABLE: $t\n";
    $res = $conn->query("DESCRIBE $t");
    if($res) {
        while($row = $res->fetch_assoc()) {
            echo "  " . $row['Field'] . " | " . $row['Type'] . "\n";
        }
    } else {
        echo "  Table does not exist\n";
    }
}
?>
