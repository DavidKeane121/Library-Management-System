<?php
include 'config.php'; // make sure the path is correct

// Simple test query
$sql = "SELECT * FROM Users LIMIT 1";
$result = $conn->query($sql);

if ($result) {
    echo "Database connection successful!<br>";
    echo "Sample user data:<br>";
    $row = $result->fetch_assoc();
    print_r($row);
} else {
    echo "Query failed: " . $conn->error;
}
?>
