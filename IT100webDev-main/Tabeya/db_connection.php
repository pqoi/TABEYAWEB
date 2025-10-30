<?php
// Database configuration
$host = 'localhost';      
$user = 'root';          
$password = '';           
$dbname = 'user';         
// Create connection with error reporting
try {
    $conn = new mysqli($host, $user, $password, $dbname);
    
    // Check connection
    if ($conn->connect_errno) {
        throw new Exception("Database Connection Failed: " . $conn->connect_error);
    }
    
    // Optional: Set character set to ensure proper character handling
    $conn->set_charset("utf8mb4");
    
    echo "Database connection successful!";
} catch (Exception $e) {
    // Log the error or display a user-friendly message
    error_log($e->getMessage());
    die("Sorry, there was a problem connecting to the database. Please try again later.");
}
?>