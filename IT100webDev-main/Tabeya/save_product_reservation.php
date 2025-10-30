<?php
// Ensure strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Disable any potential output before JSON
ob_clean();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user";

// Set JSON header at the very beginning
header('Content-Type: application/json');

// Check if this is an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    echo json_encode([
        "status" => "error", 
        "message" => "Invalid request method"
    ]);
    exit;
}

try {
    // Database connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Enable transactions for data integrity
    $conn->begin_transaction();

    // Validate and parse the product data
    $products_json = $_POST['selected_products'] ?? '[]';
    $products = json_decode($products_json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON for selected products");
    }

    $total_price = $_POST['total_price'] ?? 0;

    // First, insert reservation record
    $reservation_stmt = $conn->prepare("INSERT INTO reservations (total_price) VALUES (?)");
    
    if (!$reservation_stmt) {
        throw new Exception("Prepare reservation statement failed: " . $conn->error);
    }

    $reservation_stmt->bind_param("d", $total_price);
    
    if (!$reservation_stmt->execute()) {
        throw new Exception("Failed to create reservation: " . $reservation_stmt->error);
    }

    // Get the last inserted reservation ID
    $reservation_id = $conn->insert_id;
    $reservation_stmt->close();

    // Prepare statement for reservation items
    $items_stmt = $conn->prepare("INSERT INTO reservation_items 
        (reservation_id, product_name, quantity, unit_price, total_price) 
        VALUES (?, ?, ?, ?, ?)");

    if (!$items_stmt) {
        throw new Exception("Prepare items statement failed: " . $conn->error);
    }

    $insertion_errors = [];

    // Insert each product
    foreach ($products as $product) {
        $product_name = $product['name'] ?? '';
        $quantity = $product['quantity'] ?? 0;
        $unit_price = $product['price'] ?? 0;
        $item_total_price = $quantity * $unit_price;

        $items_stmt->bind_param("issdd", 
            $reservation_id, 
            $product_name, 
            $quantity, 
            $unit_price, 
            $item_total_price
        );

        if (!$items_stmt->execute()) {
            $insertion_errors[] = [
                'product' => $product_name,
                'error' => $items_stmt->error
            ];
        }
    }

    // Check for insertion errors
    if (!empty($insertion_errors)) {
        $conn->rollback();
        echo json_encode([
            "status" => "error", 
            "message" => "Failed to save some products",
            "errors" => $insertion_errors
        ]);
        exit;
    }

    // Commit transaction
    $conn->commit();

    // Close statements
    $items_stmt->close();
    $conn->close();

    // Success response
    echo json_encode([
        "status" => "success", 
        "message" => "Reservation saved successfully!",
        "reservation_id" => $reservation_id
    ]);
    exit;

} catch (Exception $e) {
    // Rollback transaction in case of error
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }

    // Error response
    echo json_encode([
        "status" => "error", 
        "message" => $e->getMessage()
    ]);
    exit;
}

?>