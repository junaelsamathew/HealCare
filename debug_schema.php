<?php
include 'includes/db_connect.php';

function list_cols($table, $conn) {
    echo "Table: $table\n";
    $res = $conn->query("SHOW COLUMNS FROM $table");
    if (!$res) {
        echo "Error: Table $table not found.\n";
        return;
    }
    while($row = $res->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    echo "\n";
}

list_cols('admissions', $conn);
list_cols('lab_tests', $conn);
list_cols('patient_profiles', $conn);
?>
