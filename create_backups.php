<?php
/**
 * HealCare Database Backup Script
 * Creates a full backup and individual table backups.
 */

$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "healcare";
$backupDir = __DIR__ . '/backups';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$date = date('Y-m-d_H-i-s');
$fullBackupFile = $backupDir . "/full_backup_$date.sql";

echo "Starting backup process...\n";

// 1. Create Full Backup using mysqldump
$command = "mysqldump -u $username --databases $dbname > \"$fullBackupFile\"";
exec($command, $output, $returnVar);

if ($returnVar === 0) {
    echo "Full database backup created: " . basename($fullBackupFile) . "\n";
} else {
    echo "Error creating full backup.\n";
}

// 2. Create Individual Table Backups
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    $tableFile = $backupDir . "/table_{$table}_$date.sql";
    $tableCommand = "mysqldump -u $username $dbname $table > \"$tableFile\"";
    exec($tableCommand, $tableOutput, $tableReturn);
    
    if ($tableReturn === 0) {
        echo "Table backup created: $table\n";
    } else {
        echo "Error backing up table: $table\n";
    }
}

$conn->close();
echo "Backup process finished. All files are in the 'backups/' directory.\n";
?>
