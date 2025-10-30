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
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $review = $_POST['review'];
    $rating = intval($_POST['rating']);

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO review_table (user_name, user_review, user_rating) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $username, $review, $rating);

    // Execute the statement
    if ($stmt->execute()) {
        echo "Review submitted successfully";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>