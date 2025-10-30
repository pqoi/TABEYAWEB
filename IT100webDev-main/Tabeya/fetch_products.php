<?php
header('Content-Type: application/json'); // Set content type

// Start output buffering
ob_start();
try {
    // Database connection details
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "user";
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["error" => "Database connection failed"]);
        exit;
    }

    // Fetch data, ordered by ID to ensure consistent category filtering
    $sql = "SELECT id, name, description, price FROM products ORDER BY id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        echo json_encode($products);
    } else {
        echo json_encode([]);
    }

    $conn->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

// Get the output buffer
$output = ob_get_clean();

// Check for unexpected output
if (ob_get_length() > 0) {
    error_log("Unexpected output detected: " . $output);
}

// Send the clean JSON response
echo $output;