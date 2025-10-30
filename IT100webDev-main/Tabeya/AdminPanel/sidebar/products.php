<?php
include 'db_connection.php';

// Add Product
if(isset($_POST['add_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    // Remove currency symbol and comma from price
    $price_input = str_replace('₱', '', str_replace(',', '', $_POST['price']));
    $price = floatval($price_input);

    // Insert product
    $sql = "INSERT INTO products (name, description, price) VALUES ('$name', '$description', $price)";
    $conn->query($sql);
}

// Update Product
if(isset($_POST['update_product'])) {
    $id = intval($_POST['product_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = floatval($_POST['price']);

    // Update query
    $sql = "UPDATE products SET 
            name='$name', 
            description='$description', 
            price=$price 
            WHERE id=$id";
    $conn->query($sql);
}

// Delete Product
if(isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $sql = "DELETE FROM products WHERE id = $delete_id";
    $conn->query($sql);

    // Reorder IDs after deletion
    $conn->query("SET @row_number = 0");
    $conn->query("UPDATE products SET id = (@row_number:=@row_number + 1) ORDER BY id");
    
    // Reset auto-increment
    $conn->query("ALTER TABLE products AUTO_INCREMENT = 1");
}

// Fetch Products
$products_result = $conn->query("SELECT * FROM products");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="stylesheet" href="../CSS/productDesign.css">
</head>
<body>
    
    <div class="header-container">
        <h1>Manage Products</h1>
        <button id="add-product-btn">Add New Product</button>
    </div>

    <!-- Add Product Modal -->
    <div id="add-product-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <form method="POST" action="">
                <h2>Add New Product</h2>
                <div>
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div>
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" required></textarea>
                </div>
                <div>
                    <label for="price">Price</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>
                <div>
                    <button type="submit" name="add_product">Add Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Product List -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($product = $products_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td><?php echo $product['name']; ?></td>
                    <td><?php echo $product['description']; ?></td>
                    <td>₱<?php echo number_format($product['price'], 2); ?></td>
                    <td>
                        <a href="?edit_id=<?php echo $product['id']; ?>" class="edit-btn">Edit</a>
                        <a href="?delete_id=<?php echo $product['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

   <!-- Edit Product Modal -->
    <?php 
    if(isset($_GET['edit_id'])) {
        $edit_id = intval($_GET['edit_id']);
        $edit_result = $conn->query("SELECT * FROM products WHERE id = $edit_id");
        $edit_product = $edit_result->fetch_assoc();
    ?>
    <form method="POST" action="">
        <h2>Edit Product 
            <a href="?" style="float:right; color:red; text-decoration:none; font-size:16px;">✖ Cancel</a>
        </h2>
        <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
        <div>
            <label for="name">Product Name</label>
            <input type="text" id="name" name="name" value="<?php echo $edit_product['name']; ?>" required>
        </div>
        <div>
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3" required><?php echo $edit_product['description']; ?></textarea>
        </div>
        <div>
            <label for="price">Price (₱)</label>
            <input type="number" id="price" name="price" step="0.01" min="0" required>
        </div>
        <div>
            <button type="submit" name="update_product">Update Product</button>
        </div>
    </form>
    <?php } ?>

    <script>
        // Modal functionality
        const addProductBtn = document.getElementById('add-product-btn');
        const addProductModal = document.getElementById('add-product-modal');
        const closeModal = document.querySelector('.close-modal');
        
        addProductBtn.addEventListener('click', () => {
            addProductModal.style.display = 'block';
        });

        closeModal.addEventListener('click', () => {
            addProductModal.style.display = 'none';
        });

        window.addEventListener('click', (event) => {
            if (event.target === addProductModal) {
                addProductModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>