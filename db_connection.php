
<?php
// db_connection.php - Database connection file

// Database configuration
$host = "localhost";
$username = "root";
$password = "aadhi";
$database = "pharmacy_db";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>