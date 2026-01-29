<?php
include 'includes/db_connect.php';

if (isset($_GET['category_name'])) {
    $cat_name = $_GET['category_name'];
    
    // Find category ID
    $stmt = $conn->prepare("SELECT category_id FROM lab_categories WHERE category_name = ?");
    $stmt->bind_param("s", $cat_name);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $cat_id = $row['category_id'];
        
        $stmt_t = $conn->prepare("SELECT test_name FROM lab_test_catalog WHERE category_id = ?");
        $stmt_t->bind_param("i", $cat_id);
        $stmt_t->execute();
        $res_t = $stmt_t->get_result();
        
        $tests = [];
        while ($t_row = $res_t->fetch_assoc()) {
            $tests[] = $t_row['test_name'];
        }
        
        header('Content-Type: application/json');
        echo json_encode($tests);
    } else {
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>
