<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user";

$conn = new mysqli($servername, $username, $password, $dbname);

// Include the database functions
require_once 'db_functions.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to reset auto-increment to the maximum existing ID
function resetAutoIncrement($conn) {
    // Find the maximum existing ID
    $max_id_result = $conn->query("SELECT MAX(id) as max_id FROM userinfo");
    $max_id_row = $max_id_result->fetch_assoc();
    $max_id = $max_id_row['max_id'] ? $max_id_row['max_id'] : 0;

    // Reset the auto-increment to the maximum ID + 1
    $conn->query("ALTER TABLE userinfo AUTO_INCREMENT = " . ($max_id + 1));
}

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['id']) && isset($_POST['status'])) {
    $id = $conn->real_escape_string($_POST['id']);
    $status = $conn->real_escape_string($_POST['status']);
    $update_query = "UPDATE userinfo SET status = '$status' WHERE id = '$id'";
    $conn->query($update_query);
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle delete
if (isset($_POST['delete_id'])) {
    $delete_id = $conn->real_escape_string($_POST['delete_id']);
    
    if (deleteReservation($conn, $delete_id)) {
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        // Optional: Add error handling or message display
        echo "Failed to delete the reservation.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Reservation Info</title>
    <link rel="stylesheet" href="../CSS/reservationDesign.css">
</head>
<body>
    <h1>Reservations</h1>

    <!-- Display User Info -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Guests</th>
                <th>Time</th>
                <th>Date</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch data from the userinfo table
            $result = $conn->query("SELECT * FROM userinfo ORDER BY id");
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['full_name']}</td>
                        <td>{$row['email']}</td>
                        <td>{$row['guests']}</td>
                        <td>{$row['time']}</td>
                        <td>{$row['date']}</td>
                        <td>{$row['phone']}</td>
                        <td>
                            <form method='POST' class='status-form'>
                                <input type='hidden' name='id' value='{$row['id']}'>
                                <select name='status' onchange='this.form.submit()'>
                                    <option value='pending' " . ($row['status'] == 'pending' ? 'selected' : '') . ">Pending</option>
                                    <option value='confirmed' " . ($row['status'] == 'confirmed' ? 'selected' : '') . ">Confirmed</option>
                                    <option value='canceled' " . ($row['status'] == 'canceled' ? 'selected' : '') . ">Canceled</option>
                                </select>
                                <input type='hidden' name='update_status' value='1'>
                            </form>
                        </td>
                        <td>
                            <form method='POST'>
                                <input type='hidden' name='delete_id' value='{$row['id']}'>
                                <button type='submit' class='delete-btn' onclick='return confirm(\"Are you sure you want to delete this reservation?\")'>Delete</button>
                            </form>
                        </td>
                      </tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>

<?php
$conn->close();
?>