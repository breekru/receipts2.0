<head>
        <link rel="icon" href="icons/ReceiptLogger.png" type="image/png">
        <title>Login - Receipt Logger</title>
            <link rel="stylesheet" href="stylesheet_login.css">
</head>
<body>
    
     <img class="login-container" width="150px" src="icons/ReceiptLogger.png"/?>
     <br><br>
     <div class="login-container">
         <div class="login-box">
             <h2>Login</h2>
<form action="login.php" method="post">
    <div class="textbox">
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" required><br>
    </div>
    
    <div class="textbox">
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required><br>
    </div>
    
    <button type="submit" name="login" class="btn">Login</button>
</form>
<br><br><br>
<a class="btn" href="register.php">Click here to register a New User</a>
<br><br><br>

<?php
session_start();

// Include the external database configuration file
require('/home/blkfarms/secure/db_config.php');

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    // Query the database for the user
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Check if the user is approved
        if ($user['approved'] == 1) {
            
        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Start a session and store user info
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            
            // Redirect to the index page
            header("Location: index.php");
            exit();
            } else {
                echo "Invalid password!";
            }
        } else {
            echo "Your account has not been approved. Please contact the administrator.";
        }
    } else {
        echo "No user found with that username.";
    }
}
?>
</div>
</div>
</body>
<?php
$conn->close();
?>
