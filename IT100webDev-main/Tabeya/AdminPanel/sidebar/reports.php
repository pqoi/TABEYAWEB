<?php
// Database connection
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

// Handle historical data deletion
if (isset($_POST['delete_historical_data'])) {
    $conn->query("TRUNCATE TABLE historical_sales_report");
    
    header("Location: reports.php");
    exit();
}

// Function to save current data to historical tables
function saveCurrentDataToHistory($conn) {
    // Save Sales Data
    $sales_query = "
        SELECT 
            ri.product_name, 
            SUM(ri.quantity) as total_quantity, 
            SUM(ri.total_price) as total_sales
        FROM 
            reservation_items ri
        GROUP BY 
            ri.product_name
        ORDER BY 
            total_sales DESC
    ";
    $sales_result = $conn->query($sales_query);
    
    if ($sales_result && $sales_result->num_rows > 0) {
        $insert_sales_stmt = $conn->prepare("INSERT INTO historical_sales_report (product_name, total_quantity, total_sales) VALUES (?, ?, ?)");
        
        while ($row = $sales_result->fetch_assoc()) {
            $insert_sales_stmt->bind_param("sid", 
                $row['product_name'], 
                $row['total_quantity'], 
                $row['total_sales']
            );
            $insert_sales_stmt->execute();
        }
    }
}

// Save current data to historical tables before potentially deleting current data
saveCurrentDataToHistory($conn);

// Sales Report Query (Now from historical table)
$sales_query = "
    SELECT 
        product_name, 
        total_quantity, 
        total_sales,
        recorded_at
    FROM 
        historical_sales_report
    ORDER BY 
        total_sales DESC
";
$sales_result = $conn->query($sales_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report</title>
    <link rel="stylesheet" href="../CSS/reportsDesign.css">
    <style>
        .delete-btn {
            background-color: red;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            margin-left: 10px;
            margin-right: auto;
        }
    </style>
</head>
<body>
    <h1>Sales Report</h1>
    
    <!-- Sales Report -->
    <div class="report-section">
            <form method="POST" style="display:inline;">
                <input type="hidden" name="delete_historical_data" value="1">
                <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete all historical sales data?')">Delete Sales History</button>
            </form>
        </h2>
        <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Total Quantity</th>
                <th>Total Sales (₱)</th>
                <th>Recorded Date</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_overall_sales = 0;
            if ($sales_result && $sales_result->num_rows > 0) {
                while ($row = $sales_result->fetch_assoc()) { 
                    $total_overall_sales += $row['total_sales'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo number_format($row['total_quantity'], 0); ?></td>
                        <td><?php echo number_format($row['total_sales'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['recorded_at']); ?></td>
                    </tr>
            <?php } ?>
                <tr style="font-weight: bold; background-color: #f2f2f2;">
                    <td>Total</td>
                    <td></td>
                    <td><?php echo number_format($total_overall_sales, 2); ?></td>
                    <td></td>
                </tr>
            <?php } else { ?>
                <tr>
                    <td colspan="4">No sales history available</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    </div>
</body>
</html>

<?php
$conn->close();
?>