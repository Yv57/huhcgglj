<?php
$host = "localhost"; // Change to your database host
$username = "root"; // Change to your database username
$password = ""; // Change to your database password
$database = "assignment_db"; // Change to your database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully";
?>