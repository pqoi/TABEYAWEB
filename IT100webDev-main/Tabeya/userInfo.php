<?php
// Disable error reporting for production
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user";

// Function to reset auto-increment
function resetTableAutoIncrement($conn, $table) {
    $reset_query = "ALTER TABLE $table AUTO_INCREMENT = 1";
    if ($conn->query($reset_query) === TRUE) {
        return true;
    }
    return false;
}

// Check if this is an AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// If it's an AJAX request, return JSON
if ($is_ajax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set JSON header
    header('Content-Type: application/json');

    // Database connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
    }

    // Extract data from the form
    $full_name = $_POST['full-name'] ?? null;
    $email = $_POST['email'] ?? null;
    $guests = $_POST['guests'] ?? null;
    $time = $_POST['time'] ?? null;
    $date = $_POST['date'] ?? null;
    $phone = $_POST['phone'] ?? null;

    // Validate required fields
    if (!$full_name || !$email || !$guests || !$time || !$date || !$phone) {
        echo json_encode([
            "status" => "error", 
            "message" => "All fields are required.",
            "received_data" => $_POST
        ]);
        exit;
    }

    // Prepare SQL insert statement
    $stmt = $conn->prepare("INSERT INTO userinfo (full_name, email, guests, time, date, phone, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("ssisss", $full_name, $email, $guests, $time, $date, $phone);

    // Execute the statement
    if ($stmt->execute()) {
        // Get the last inserted ID
        $reservation_id = $conn->insert_id;
        echo json_encode([
            "status" => "success", 
            "message" => "Reservation saved successfully!",
            "reservation_id" => $reservation_id
        ]);
    } else {
        echo json_encode([
            "status" => "error", 
            "message" => "Failed to save reservation.",
            "error_details" => $stmt->error
        ]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// Handle table reset operation 
if (isset($_GET['action']) && $_GET['action'] === 'reset_table') {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
    }

    // Truncate the table and reset auto-increment
    $truncate_query = "TRUNCATE TABLE userinfo";
    $reset_success = $conn->query($truncate_query) && resetTableAutoIncrement($conn, 'userinfo');

    if ($reset_success) {
        echo json_encode([
            "status" => "success", 
            "message" => "Table reset successfully!"
        ]);
    } else {
        echo json_encode([
            "status" => "error", 
            "message" => "Failed to reset table."
        ]);
    }

    $conn->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Information</title>
    <link rel="stylesheet" href="CSS/userInfoDesign.css">
    <style>
        .product-category-nav {
            background-color: rgb(188, 24, 35); 
            position: fixed; 
            top: 0;
            left: 0; 
            right: 0;
            display: flex; 
            justify-content: center; 
            padding: 15px; 
        }

        .product-category-nav a {
            color: white;
            margin: 0 15px;
            text-decoration: none;
            font-weight: bold;
        }
        .product-category-nav a:hover,
        .product-category-nav a.active {
            color: yellow;
        }
    </style>
</head>
<body>
    <!-- Product Selection Section -->
    <div class="background">
        <img src="Photo/Reservation.jpg" alt="Background">
        <h1 class="quote">YOUR<br>SATISFACTION<br>IS OUR<br>PASSION</h1>
        <div class="rectangle-panel">
            <h1 class="product-selection-label">CHOOSE YOUR PREFERRED PRODUCTS</h1>
            <div class="continue-section">
                <button class="continue-btn" onclick="showProductSelection()">Continue to Product Selection</button>
            </div>
            <div id="product-selection-panel" class="product-selection-panel">
                <div class="product-category-nav">
                    <a href="#" data-category="1-32" class="active">Bilao</a>
                    <a href="#" data-category="33-54">Platter</a>
                    <a href="#" data-category="55-70">Rice Meal</a>
                    <a href="#" data-category="71-78">Rice</a>
                    <a href="#" data-category="79-86">Spaghetti Meals</a>
                    <a href="#" data-category="87-90">Sandwiches</a>
                    <a href="#" data-category="91-94">Snacks</a>
                    <a href="#" data-category="95-98">Dessert</a>
                </div>
                <div id="product-groups" class="product-groups">
                    <!-- Products will be dynamically loaded here -->
                </div>
                
                <div class="panel-controls">
                    <div class="total-price-container">
                        Total Price: ₱<span id="total-price">0.00</span>
                    </div>
                    <div>
                        <button class="cancel-btn" onclick="closeProductSelection()">Cancel</button>
                        <button class="submit-btn" onclick="showConfirmationPopup()">Submit Orders</button>
                    </div>
                </div>
            </div>
        </div>
    </div>  

    <script src="products.js"></script>
   <!-- Confirmation Popup -->
    <div class="popup-overlay" id="confirmation-popup">
        <div class="popup-content">
            <p>Are you sure you want to submit your orders?
            Total Orders: ₱<span id="total-reservation"></span>
            </p>
            <button onclick="confirmReservation()">Yes</button>
            <button onclick="closePopup('confirmation-popup')">No</button>
        </div>
    </div>

    <!-- Success Popup -->
    <div class="popup-overlay" id="success-popup">
        <div class="popup-content">
            <p>Orders received! Our staff will contact you shortly to get your delivery address. Thank you for choosing TABEYA!</p>
            <button onclick="redirectToReservation()">Exit to Cater Reservation</button>
        </div>
    </div>

        <script>
        // Show the confirmation popup
        function showConfirmationPopup() {
            // Get the total price from the total-price span
            const totalPrice = document.getElementById('total-price').innerText;

            // Update the total reservation amount in the confirmation popup
            document.getElementById('total-reservation').innerText = totalPrice;

            // Show the confirmation popup
            document.getElementById('confirmation-popup').style.display = 'flex';
        }

        // Close any popup
        function closePopup(popupId) {
            document.getElementById(popupId).style.display = 'none';
        }

        // Confirm reservation and show success popup
        function confirmReservation() {
            closePopup('confirmation-popup');
            document.getElementById('success-popup').style.display = 'flex';
        }

        // Redirect to the Cater Reservation page
        function redirectToReservation() {
            window.location.href = "CaterReservation.html";
        }
    </script>
</body>
</html>