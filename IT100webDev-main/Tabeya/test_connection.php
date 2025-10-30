<?php
// Include the database connection file
require_once 'db_connection.php';

// Test function to check database connectivity
function testDatabaseConnection($conn) {
    // Simple test query
    $testQuery = "SHOW TABLES";
    
    try {
        // Execute the query
        $result = $conn->query($testQuery);
        
        // Check if query was successful
        if ($result) {
            echo "Database Connection Successful! 🎉<br>";
            
            // Display available tables
            echo "Tables in the database:<br>";
            while ($row = $result->fetch_array()) {
                echo "- " . $row[0] . "<br>";
            }
            
            // Free result set
            $result->free();
        } else {
            throw new Exception("Query failed: " . $conn->error);
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Run the connection test
testDatabaseConnection($conn);

// Close the connection
$conn->close();
?>