<?php
$file = 'c:\\xampp\\htdocs\\HealCare-main\\staff_pharmacist_dashboard.php';
$content = file_get_contents($file);

$target = 'if($curr[\'quantity\'] == 0) {
                                      $m_name = mysqli_real_escape_string($conn, $curr[\'medicine_name\']);
                                      $n_msg = "URGENT: $m_name is now OUT OF STOCK.";
                                      $conn->query("INSERT INTO notifications (user_id, category, icon, color, title, message, url, priority, unread, created_at) 
                                                   VALUES (0, \'Inventory\', \'fa-box-open\', \'danger\', \'Out of Stock Alert\', \'$n_msg\', \'admin_pharmacy_inventory.php?section=low_stock\', \'High\', 1, NOW())");
                                  }';

$replacement = 'if($curr[\'quantity\'] < 20) {
                                      $m_name = mysqli_real_escape_string($conn, $curr[\'medicine_name\']);
                                      $q_left = $curr[\'quantity\'];
                                      $n_title = ($q_left == 0) ? "Critical Out of Stock Alert" : "Low Stock Alert";
                                      $n_color = ($q_left == 0) ? "danger" : "warning";
                                      $n_msg = ($q_left == 0) ? "URGENT: $m_name is now OUT OF STOCK." : "Attention: $m_name is RUNNING LOW ($q_left units left).";
                                      $n_icon = ($q_left == 0) ? "fa-box-open" : "fa-exclamation-triangle";
                                      
                                      $check_duplicate = $conn->query("SELECT id FROM notifications WHERE title = \'$n_title\' AND message LIKE \'%$m_name%\' AND unread = 1 AND DATE(created_at) = CURDATE()");
                                      if($check_duplicate->num_rows == 0) {
                                          $conn->query("INSERT INTO notifications (user_id, category, icon, color, title, message, url, priority, unread, created_at) 
                                                       VALUES (0, \'Inventory\', \'$n_icon\', \'$n_color\', \'$n_title\', \'$n_msg\', \'admin_pharmacy_inventory.php?section=low_stock\', \'High\', 1, NOW())");
                                      }
                                  }';

// Simple str_replace might fail due to whitespace. Let's use a regex that is more forgiving.
$pattern = '/if\s*\(\s*\$curr\s*\[\s*\'quantity\'\s*\]\s*==\s*0\s*\)\s*\{(.*?)mysqli_real_escape_string\(.*?\).*?n_msg\s*=\s*".*?".*?conn->query\(.*?"INSERT\s+INTO\s+notifications.*?High\', 1, NOW\(\)\)"\s*\);\s*\}/s';

if (preg_match($pattern, $content, $matches)) {
    $new_content = preg_replace($pattern, $replacement, $content);
    file_put_contents($file, $new_content);
    echo "Successfully updated stock notification logic.";
} else {
    echo "Could not find target pattern in file.";
}
?>
