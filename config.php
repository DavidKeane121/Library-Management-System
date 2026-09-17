<?php //config.php
$host = "localhost"; // Database server hostname
$db   = "LibraryDB"; // Database name
$user = "root";   // XAMPP default username
$pass = "";       // XAMPP default password 

// Create a new MySQLi connection object and attempt to connect to the database.
// $conn will be available to any script that includes this file.
$conn = new mysqli($host, $user, $pass, $db);

//Error check for the database connection if the connection fails, stop execution and show a helpful message
if ($conn->connect_error) 
{   
    // die() halts execution and outputs the message.
    die("Connection failed: " . $conn->connect_error);
}
?>
