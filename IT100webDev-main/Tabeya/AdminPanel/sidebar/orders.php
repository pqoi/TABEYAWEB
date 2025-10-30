<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user";

// Create connection
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
    $max_id_result = $conn->query("SELECT MAX(id) as max_id FROM reservations");
    $max_id_row = $max_id_result->fetch_assoc();
    
    // If no rows exist, reset to 1
    $max_id = $max_id_row['max_id'] ? $max_id_row['max_id'] : 0;

    // Reset the auto-increment to 1 if no rows, otherwise to max_id + 1
    $new_auto_increment = $max_id > 0 ? ($max_id + 1) : 1;
    $conn->query("ALTER TABLE reservations AUTO_INCREMENT = " . $new_auto_increment);
}

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['id']) && isset($_POST['status'])) {
    $id = $conn->real_escape_string($_POST['id']);
    $status = $conn->real_escape_string($_POST['status']);
    
    // Prepared statement for more secure update
    $update_stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $status, $id);
    
    if ($update_stmt->execute()) {
        // Also update the corresponding userinfo table
        $update_userinfo = $conn->prepare("UPDATE userinfo SET status = ? WHERE id = ?");
        $update_userinfo->bind_param("si", $status, $id);
        $update_userinfo->execute();
        
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error updating status: " . $conn->error;
    }
    $update_stmt->close();
}

// Handle delete operation
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

// Handle edit operation
if (isset($_POST['edit_id'])) {
    $edit_id = $conn->real_escape_string($_POST['edit_id']);
    $total_price = $conn->real_escape_string($_POST['total_price']);
    
    // Start transaction for more robust editing
    $conn->begin_transaction();
    
    try {
        // Update reservation total price
        $update_reservation_stmt = $conn->prepare("UPDATE reservations SET total_price = ? WHERE id = ?");
        $update_reservation_stmt->bind_param("di", $total_price, $edit_id);
        $update_reservation_stmt->execute();
        $update_reservation_stmt->close();

        // Delete existing items
        $delete_items_query = "DELETE FROM reservation_items WHERE reservation_id = '$edit_id'";
        $conn->query($delete_items_query);

        // Reinsert items
        if (isset($_POST['product_names']) && isset($_POST['quantities']) && isset($_POST['unit_prices'])) {
            $insert_item_stmt = $conn->prepare("INSERT INTO reservation_items 
                (reservation_id, product_name, quantity, unit_price, total_price) 
                VALUES (?, ?, ?, ?, ?)");

            foreach ($_POST['product_names'] as $index => $product_name) {
                $quantity = $_POST['quantities'][$index];
                $unit_price = $_POST['unit_prices'][$index];
                $item_total_price = $quantity * $unit_price;

                $insert_item_stmt->bind_param("issdd", 
                    $edit_id, 
                    $product_name, 
                    $quantity, 
                    $unit_price, 
                    $item_total_price
                );
                $insert_item_stmt->execute();
            }
            $insert_item_stmt->close();
        }

        // Commit transaction
        $conn->commit();
        
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "Edit failed: " . $e->getMessage();
    }
}
// Query to retrieve orders and related data
$query = "SELECT r.id, r.total_price, r.status, 
                 u.full_name,
                 GROUP_CONCAT(ri.product_name SEPARATOR '<br>') as products,
                 GROUP_CONCAT(ri.quantity SEPARATOR '<br>') as quantities,
                 GROUP_CONCAT(CONCAT('₱', FORMAT(ri.unit_price, 2)) SEPARATOR '<br>') as prices
          FROM reservations r
          LEFT JOIN reservation_items ri ON r.id = ri.reservation_id
          LEFT JOIN userinfo u ON r.id = u.id
          GROUP BY r.id
          ORDER BY r.id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <link rel="stylesheet" href="../CSS/orderDesign.css">
</head>
<body>
    <h1>Manage Orders</h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer Name</th>
                <th>Products</th>
                <th>Quantities</th>
                <th>Unit Prices</th>
                <th>Total Price</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <!-- Loop through the orders and display them in the table -->
            <?php while ($order = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $order['id']; ?></td>
                    <td><?php echo $order['full_name'] ?? 'N/A'; ?></td>
                    <td><?php echo $order['products'] ?? 'No Products'; ?></td>
                    <td><?php echo $order['quantities'] ?? 'N/A'; ?></td>
                    <td><?php echo str_replace('<br>', '<br>', $order['prices'] ?? 'N/A'); ?></td>
                    <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                    <td>
                        <form method='POST' action=''>
                            <input type='hidden' name='update_status' value='1'>
                            <input type='hidden' name='id' value='<?php echo $order['id']; ?>'>
                            <select name='status' onchange='this.form.submit()'>
                                <option value='Pending' <?php echo ($order['status'] == 'Pending' ? 'selected' : ''); ?>>Pending</option>
                                <option value='Processed' <?php echo ($order['status'] == 'Processed' ? 'selected' : ''); ?>>Processed</option>
                                <option value='Shipped' <?php echo ($order['status'] == 'Shipped' ? 'selected' : ''); ?>>Shipped</option>
                                <option value='Delivered' <?php echo ($order['status'] == 'Delivered' ? 'selected' : ''); ?>>Delivered</option>
                            </select>
                        </form>
                    </td>
                    <td class="action-btns">
                        <button onclick="openEditModal(
                            '<?php echo $order['id']; ?>',
                            '<?php echo $order['total_price']; ?>',
                            `<?php echo $order['products'] ?? ''; ?>`.split('<br>'),
                            `<?php echo $order['quantities'] ?? ''; ?>`.split('<br>'),
                            `<?php echo str_replace('₱', '', $order['prices'] ?? ''); ?>`.split('<br>')
                        )" class="edit-btn">Edit</button>
                        <form method='POST' style='margin:0;'>
                            <input type='hidden' name='delete_id' value='<?php echo $order['id']; ?>'>
                            <button type='submit' class='delete-btn' onclick='return confirm("Are you sure you want to delete this order?")'>Delete</button>
                      </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <!-- Modal for editing order details -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Order</h2>
            <form method="POST" id="editForm">
                <input type="hidden" name="edit_id" id="editId">
                <div>
                    <label>Total Price</label>
                    <input type="number" step="0.01" name="total_price" id="editTotalPrice">
                </div>
                <div id="productDetails">
                    <!-- Dynamic product details will be inserted here -->
                </div>
                <button type="button" class="add-product-btn" onclick="addProductRow()">Add Product</button>
                <button type="submit">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        //JavaScript for editing order details
        function addProductRow(productName = '', quantity = '', unitPrice = '') {
            const productDetailsDiv = document.getElementById('productDetails');
            const rowDiv = document.createElement('div');
            rowDiv.className = 'edit-product-container';
            const productTotal = quantity * unitPrice;
            rowDiv.innerHTML = `
                <div>
                    <label>Product Name</label>
                    <input type="text" name="product_names[]" value="${productName}" required>
                </div>
                <div>
                    <label>Quantity</label>
                    <input type="number" name="quantities[]" value="${quantity}" min="1" onchange="calculateProductTotal(this)" required>
                </div>
                <div>
                    <label>Unit Price (₱)</label>
                    <input type="number" step="0.01" name="unit_prices[]" value="${unitPrice}" min="0" onchange="calculateProductTotal(this)" required>
                </div>
                <div>
                    <label>Product Total</label>
                    <input type="number" step="0.01" name="product_totals[]" readonly value="${productTotal.toFixed(2)}">
                </div>
                <div>
                    <button type="button" class="remove-product-btn" onclick="removeProductRow(this)">×</button>
                </div>
            `;
            productDetailsDiv.appendChild(rowDiv);
            updateOverallTotal();
        }

        //JavaScript for calculating product total
        function calculateProductTotal(inputElement) {
            const row = inputElement.closest('.edit-product-container');
            const quantity = row.querySelector('input[name="quantities[]"]').value;
            const unitPrice = row.querySelector('input[name="unit_prices[]"]').value;
            const productTotal = row.querySelector('input[name="product_totals[]"]');
            productTotal.value = (quantity * unitPrice).toFixed(2);
            updateOverallTotal();
        }
        //JavaScript for removing product row
        function removeProductRow(buttonElement) {
            buttonElement.closest('.edit-product-container').remove();
            updateOverallTotal();
        }
        //JavaScript for updating overall total
        function updateOverallTotal() {
            const productTotals = document.querySelectorAll('input[name="product_totals[]"]');
            const totalPriceInput = document.getElementById('editTotalPrice');
            const overallTotal = Array.from(productTotals).reduce((sum, total) => sum + parseFloat(total.value || 0), 0);
            totalPriceInput.value = overallTotal.toFixed(2);
        }
        //JavaScript for opening edit modal
        function openEditModal(id, totalPrice, products, quantities, prices) {
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('editId').value = id;
            document.getElementById('editTotalPrice').value = totalPrice;

            const productDetailsDiv = document.getElementById('productDetails');
            productDetailsDiv.innerHTML = '';

            products.forEach((product, index) => {
                if (product.trim()) {
                    addProductRow(product, quantities[index], prices[index]);
                }
            });
            updateOverallTotal();
        }
        //JavaScript for closing edit modal
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>