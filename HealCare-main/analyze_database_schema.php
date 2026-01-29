<?php
/**
 * Database Schema Analyzer
 * This script analyzes the HealCare database and generates a comprehensive list
 * of all tables and their attributes (columns)
 */

include 'includes/db_connect.php';

// Get all tables in the database
$tables_query = "SHOW TABLES";
$tables_result = $conn->query($tables_query);

$database_schema = [];

if ($tables_result) {
    while ($table_row = $tables_result->fetch_array()) {
        $table_name = $table_row[0];
        
        // Get column information for each table
        $columns_query = "DESCRIBE `$table_name`";
        $columns_result = $conn->query($columns_query);
        
        $columns = [];
        if ($columns_result) {
            while ($column = $columns_result->fetch_assoc()) {
                $columns[] = [
                    'Field' => $column['Field'],
                    'Type' => $column['Type'],
                    'Null' => $column['Null'],
                    'Key' => $column['Key'],
                    'Default' => $column['Default'],
                    'Extra' => $column['Extra']
                ];
            }
        }
        
        $database_schema[$table_name] = $columns;
    }
}

// Generate output
echo "<!DOCTYPE html>\n";
echo "<html lang='en'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "    <title>HealCare Database Schema</title>\n";
echo "    <style>\n";
echo "        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0f172a; color: #e2e8f0; padding: 20px; }\n";
echo "        .container { max-width: 1400px; margin: 0 auto; }\n";
echo "        h1 { color: #3b82f6; text-align: center; margin-bottom: 10px; }\n";
echo "        .summary { text-align: center; color: #94a3b8; margin-bottom: 30px; }\n";
echo "        .table-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; margin-bottom: 25px; overflow: hidden; }\n";
echo "        .table-header { background: linear-gradient(135deg, #3b82f6, #2563eb); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }\n";
echo "        .table-name { font-size: 20px; font-weight: 700; color: white; }\n";
echo "        .column-count { background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }\n";
echo "        table { width: 100%; border-collapse: collapse; }\n";
echo "        th { background: #334155; padding: 12px; text-align: left; font-weight: 600; color: #cbd5e1; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }\n";
echo "        td { padding: 12px; border-bottom: 1px solid #334155; font-size: 14px; }\n";
echo "        tr:hover { background: rgba(59, 130, 246, 0.05); }\n";
echo "        .key-badge { background: #10b981; color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }\n";
echo "        .key-badge.PRI { background: #ef4444; }\n";
echo "        .key-badge.UNI { background: #f59e0b; }\n";
echo "        .key-badge.MUL { background: #3b82f6; }\n";
echo "        .type { color: #a78bfa; font-family: 'Courier New', monospace; }\n";
echo "        .null-yes { color: #94a3b8; }\n";
echo "        .null-no { color: #10b981; font-weight: 600; }\n";
echo "        .extra { color: #fbbf24; font-style: italic; font-size: 12px; }\n";
echo "        .search-box { margin-bottom: 20px; }\n";
echo "        .search-box input { width: 100%; padding: 15px; background: #1e293b; border: 1px solid #334155; border-radius: 8px; color: white; font-size: 16px; }\n";
echo "        .search-box input:focus { outline: none; border-color: #3b82f6; }\n";
echo "    </style>\n";
echo "    <script>\n";
echo "        function searchTables() {\n";
echo "            const input = document.getElementById('searchInput').value.toLowerCase();\n";
echo "            const tables = document.getElementsByClassName('table-card');\n";
echo "            for (let table of tables) {\n";
echo "                const tableName = table.getAttribute('data-table-name').toLowerCase();\n";
echo "                const tableContent = table.textContent.toLowerCase();\n";
echo "                if (tableName.includes(input) || tableContent.includes(input)) {\n";
echo "                    table.style.display = 'block';\n";
echo "                } else {\n";
echo "                    table.style.display = 'none';\n";
echo "                }\n";
echo "            }\n";
echo "        }\n";
echo "    </script>\n";
echo "</head>\n";
echo "<body>\n";
echo "    <div class='container'>\n";
echo "        <h1>🏥 HealCare Database Schema</h1>\n";
echo "        <div class='summary'>\n";
echo "            <strong>" . count($database_schema) . "</strong> Tables Found | Generated on " . date('F d, Y \a\t h:i A') . "\n";
echo "        </div>\n";
echo "        <div class='search-box'>\n";
echo "            <input type='text' id='searchInput' onkeyup='searchTables()' placeholder='🔍 Search tables or columns...'>\n";
echo "        </div>\n";

foreach ($database_schema as $table_name => $columns) {
    echo "        <div class='table-card' data-table-name='$table_name'>\n";
    echo "            <div class='table-header'>\n";
    echo "                <span class='table-name'>📋 $table_name</span>\n";
    echo "                <span class='column-count'>" . count($columns) . " columns</span>\n";
    echo "            </div>\n";
    echo "            <table>\n";
    echo "                <thead>\n";
    echo "                    <tr>\n";
    echo "                        <th>Field Name</th>\n";
    echo "                        <th>Data Type</th>\n";
    echo "                        <th>Null</th>\n";
    echo "                        <th>Key</th>\n";
    echo "                        <th>Default</th>\n";
    echo "                        <th>Extra</th>\n";
    echo "                    </tr>\n";
    echo "                </thead>\n";
    echo "                <tbody>\n";
    
    foreach ($columns as $column) {
        $key_badge = '';
        if (!empty($column['Key'])) {
            $key_badge = "<span class='key-badge {$column['Key']}'>{$column['Key']}</span>";
        }
        
        $null_class = $column['Null'] === 'YES' ? 'null-yes' : 'null-no';
        $default_value = $column['Default'] !== null ? htmlspecialchars($column['Default']) : '<em>NULL</em>';
        $extra = !empty($column['Extra']) ? "<span class='extra'>{$column['Extra']}</span>" : '-';
        
        echo "                    <tr>\n";
        echo "                        <td><strong>{$column['Field']}</strong></td>\n";
        echo "                        <td class='type'>{$column['Type']}</td>\n";
        echo "                        <td class='$null_class'>{$column['Null']}</td>\n";
        echo "                        <td>$key_badge</td>\n";
        echo "                        <td>$default_value</td>\n";
        echo "                        <td>$extra</td>\n";
        echo "                    </tr>\n";
    }
    
    echo "                </tbody>\n";
    echo "            </table>\n";
    echo "        </div>\n";
}

echo "    </div>\n";
echo "</body>\n";
echo "</html>\n";

// Also generate a text file version
$text_output = "HEALCARE DATABASE SCHEMA\n";
$text_output .= "Generated on: " . date('F d, Y \a\t h:i A') . "\n";
$text_output .= "Total Tables: " . count($database_schema) . "\n";
$text_output .= str_repeat("=", 80) . "\n\n";

foreach ($database_schema as $table_name => $columns) {
    $text_output .= "TABLE: $table_name\n";
    $text_output .= "Columns: " . count($columns) . "\n";
    $text_output .= str_repeat("-", 80) . "\n";
    
    foreach ($columns as $column) {
        $text_output .= sprintf(
            "  %-30s %-20s %-5s %-5s %-15s %s\n",
            $column['Field'],
            $column['Type'],
            $column['Null'],
            $column['Key'],
            $column['Default'] ?? 'NULL',
            $column['Extra']
        );
    }
    
    $text_output .= "\n";
}

file_put_contents('DATABASE_SCHEMA.txt', $text_output);
echo "<script>console.log('Text version saved to DATABASE_SCHEMA.txt');</script>";

$conn->close();
?>
