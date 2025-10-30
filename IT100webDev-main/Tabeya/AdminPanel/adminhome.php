<?php
session_start();

// include database connection file
if(!isset($_SESSION["username"]))
{
	header("location:login.php");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="CSS/admin.css"> 
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2>Admin </h2>
            <ul>
                <li><a href="sidebar/dashboard.php" target="content-frame">Dashboard</a></li>
                <li><a href="sidebar/user.php" target="content-frame">Users</a></li>
                <li><a href="sidebar/products.php" target="content-frame">Products</a></li>
                <li><a href="sidebar/reservation.php" target="content-frame">Reservations</a></li>
                <li><a href="sidebar/orders.php" target="content-frame">Orders</a></li>
                <li><a href="sidebar/testimonials.php" target="content-frame">Testimonials</a></li>
                <li><a href="sidebar/reports.php" target="content-frame">Reports</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>

        <!-- Content Area -->
        <div class="content">
            <iframe name="content-frame" src="sidebar/dashboard.php"></iframe>
        </div>
    </div>
    

</body>
</html>
