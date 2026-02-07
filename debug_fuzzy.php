<?php
include 'includes/db_connect.php';
$p_id = 38; // Juna
$p_name = "Juna Elsa Mathew";
$t_name = "CBC";

$search_pat_name = mysqli_real_escape_string($conn, $p_name);
$search_test_name = mysqli_real_escape_string($conn, $t_name);

$m_query = "SELECT * FROM manual_reports WHERE ";
$m_query .= "( (patient_id = $p_id) OR (report_title LIKE '%$search_pat_name%') ) ";
$m_query .= "AND (report_title LIKE '%$search_test_name%' OR report_type LIKE '%$search_test_name%' OR created_at >= NOW() - INTERVAL 48 HOUR) ";
$m_query .= "ORDER BY (CASE WHEN report_title LIKE '%$search_test_name%' THEN 1 ELSE 2 END) ASC, created_at DESC LIMIT 1";

$res = $conn->query($m_query);
if ($row = $res->fetch_assoc()) {
    echo "MATCH FOUND: " . $row['report_title'] . " | Path: " . $row['file_path'] . "\n";
} else {
    echo "NO MATCH FOUND\n";
    echo "DEBUG: Query was: " . $m_query . "\n";
    
    // Check all reports for this patient
    $all = $conn->query("SELECT * FROM manual_reports WHERE patient_id = $p_id OR report_title LIKE '%$search_pat_name%'");
    echo "ALL REPORTS FOR PATIENT ($p_id / $p_name):\n";
    while($r = $all->fetch_assoc()) {
        echo "- " . $r['report_title'] . " (ID: " . $r['report_id'] . ") | Date: " . $r['created_at'] . "\n";
    }
}
?>
