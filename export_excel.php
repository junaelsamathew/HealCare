<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_data'])) {
    $data = json_decode($_POST['report_data'], true);
    $filename = $_POST['filename'] ?? 'report';

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename.xls\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<table border="1">';
    // Write headers if we have them...
    // Actually, it might be easier to just pass the HTML of the table itself
}
?>
