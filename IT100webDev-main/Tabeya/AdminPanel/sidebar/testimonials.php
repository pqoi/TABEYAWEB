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

// Fetch all testimonials from the database
$sql = "SELECT * FROM review_table ORDER BY datetime DESC";
$testimonials_result = $conn->query($sql);

// Process the review status update
if (isset($_GET["update_id"]) && isset($_GET["status"])) {
    $testimonial_id = $_GET["update_id"];
    $new_status = $_GET["status"];

    // Update the testimonial status
    $sql = "UPDATE review_table SET status = ? WHERE review_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $testimonial_id);

    if ($stmt->execute()) {
        echo "<script>alert('Review status updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating review status: " . $stmt->error . "');</script>";
    }

    $stmt->close();

    // Refresh the page to show updated results
    echo "<script>window.location.href = 'testimonials.php';</script>";
}

// Process the review deletion
if (isset($_GET["delete_id"])) {
    $testimonial_id = $_GET["delete_id"];

    // Delete the testimonial
    $sql = "DELETE FROM review_table WHERE review_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $testimonial_id);
    // Execute the statement
    if ($stmt->execute()) {
        // Check if this was the last review
        $check_count_sql = "SELECT COUNT(*) as count FROM review_table";
        $count_result = $conn->query($check_count_sql);
        $count_row = $count_result->fetch_assoc();
        
        if ($count_row['count'] == 0) {
            // If no reviews left, reset auto-increment to 1
            $reset_auto_increment_sql = "ALTER TABLE review_table AUTO_INCREMENT = 1";
            $conn->query($reset_auto_increment_sql);
        }

        echo "<script>alert('Review deleted successfully!');</script>";
    } else {
        echo "<script>alert('Error deleting review: " . $stmt->error . "');</script>";
    }

    $stmt->close();

    // Refresh the page to show updated results
    echo "<script>window.location.href = 'testimonials.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews</title>
    <link rel="stylesheet" href="../CSS/testimonialsDesign.css">
</head>
<body>
<h1>Manage Reviews</h1>

<!-- Reviews List -->
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Review</th>
            <th>Rating</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($review = $testimonials_result->fetch_assoc()) { ?>
            <tr>
            <!--Display review details including ID, user name, review content, and user rating out of 5.-->
                <td><?php echo $review['review_id']; ?></td>
                <td><?php echo htmlspecialchars($review['user_name']); ?></td>
                <td><?php echo htmlspecialchars($review['user_review']); ?></td>
                <td><?php echo $review['user_rating']; ?>/5</td>
                <td><?php 
                    // Process and display the datetime value from the review:
                    $timestamp = $review['datetime'];
                    
                    // If it's already a valid timestamp format, use it directly
                    if (strtotime($timestamp) !== false) {
                        echo htmlspecialchars($timestamp);
                    } 
                    // If it's a numeric Unix timestamp, convert it to a readable format (Y-m-d H:i:s).
                    elseif (is_numeric($timestamp)) {
                        echo htmlspecialchars(date('Y-m-d H:i:s', $timestamp));
                    } 
                    // If all else fails, show the original value
                    else {
                        echo htmlspecialchars($timestamp);
                    }
                ?></td>
                <!--Display the review status, defaulting to 'Pending' if not set-->
                <td class="status-<?php echo strtolower($review['status'] ?? 'Pending'); ?>">
                    <?php echo $review['status'] ?? 'Pending'; ?>
                </td>
                <!--Render action links to approve, reject, or delete a review based on its current status.-->
                <td class="action-links">
                    <?php if ($review['status'] !== 'Approved'): ?>
                        <a href="testimonials.php?update_id=<?php echo $review['review_id']; ?>&status=Approved" class="approve">Approve</a>
                    <?php endif; ?>
                    <?php if ($review['status'] !== 'Rejected'): ?>
                        <a href="testimonials.php?update_id=<?php echo $review['review_id']; ?>&status=Rejected" class="reject">Reject</a>
                    <?php endif; ?>
                    <a href="testimonials.php?delete_id=<?php echo $review['review_id']; ?>" class="delete" onclick="return confirm('Are you sure you want to delete this review?');">Delete</a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>