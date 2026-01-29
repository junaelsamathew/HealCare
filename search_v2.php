<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "HealCare";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function search($conn, $name) {
    echo "SEARCH FOR: $name\n";
    $q = "SELECT u.user_id, u.username, u.user_role, r.name 
          FROM users u 
          JOIN registrations r ON u.registration_id = r.registration_id 
          WHERE r.name LIKE '%$name%'";
    $res = $conn->query($q);
    if($res) {
        while($row = $res->fetch_assoc()) {
            echo "USER: " . $row['user_id'] . " | " . $row['username'] . " | " . $row['user_role'] . " | " . $row['name'] . "\n";
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}

search($conn, 'Ciya');
search($conn, 'Gigi');
?>
