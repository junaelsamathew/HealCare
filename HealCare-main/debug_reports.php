<?php
include 'includes/db_connect.php';
$res = $conn->query("SELECT id, user_role, report_type, report_category, report_title FROM manual_reports ORDER BY id DESC LIMIT 5");
echo "ID | Role | Type | Category | Title\n";
echo "---|---|---|---|---\n";
while($row = $res->fetch_assoc()) {
    echo "{$row['id']} | {$row['user_role']} | {$row['report_type']} | {$row['report_category']} | {$row['report_title']}\n";
}
?>
