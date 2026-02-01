<?php
include 'includes/db_connect.php';
$conn->query("ALTER TABLE appointments ADD COLUMN external_age INT NULL");
$conn->query("ALTER TABLE appointments ADD COLUMN external_blood_group VARCHAR(10) NULL");
echo "Schema updated with external_age and external_blood_group.";
?>
