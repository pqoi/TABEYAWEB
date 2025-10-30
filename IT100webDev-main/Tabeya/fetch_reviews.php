<?php
// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Fetch only approved reviews
$sql = "SELECT review_id, user_name, user_review, user_rating FROM review_table WHERE status = 'Approved' ORDER BY datetime DESC";
$result = $conn->query($sql);

$reviews = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

// Send reviews as JSON
header('Content-Type: application/json');
echo json_encode($reviews);

$conn->close();
?>