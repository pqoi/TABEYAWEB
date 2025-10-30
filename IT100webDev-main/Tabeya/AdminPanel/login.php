<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$db = "user";
// Start a session
session_start();
// Connect to the database
$data = mysqli_connect($host, $user, $password, $db);

if ($data === false) {
    die("Connection error");
}

$login_error = ""; // Variable to store login error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $username = mysqli_real_escape_string($data, $_POST["username"]);
    $password = mysqli_real_escape_string($data, $_POST["password"]);

    // Use prepared statement to prevent SQL injection
    $sql = "SELECT * FROM login WHERE username = ? AND password = ?";
    $stmt = mysqli_prepare($data, $sql);
    // Execute a prepared statement
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $username, $password);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        // Check if the user exists and has the correct usertype
        if ($result) {
            $row = mysqli_fetch_array($result);

            // Check if the user exists and has the correct usertype
            if ($row) {
                if ($row["usertype"] == "user") {
                    $_SESSION["username"] = $username;
                    header("location:userhome.php");
                    exit();
                } elseif ($row["usertype"] == "admin") {
                    $_SESSION["username"] = $username;
                    header("location:adminhome.php");
                    exit();
                }
            }
        }
    }

    // If no matching user found
    $login_error = "Incorrect username or password";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <link rel="stylesheet" href="CSS/loginDesign.css">
    
</head>
<body>
    <div class="login-container">
        <h1>Login Form</h1>
        <form action="" method="POST">
            <?php if (!empty($login_error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>
            <!--Username inputfield-->
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" placeholder="Enter your username" required 
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            <!--Password input field-->
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
            <!--Login button-->
            <input type="submit" value="Login">
        </form>
    </div>
</body>
</html>