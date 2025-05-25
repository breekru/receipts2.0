<head>
        <link rel="icon" href="icons/ReceiptLogger.png" type="image/png">
        <title>Registration - Receipt Logger</title>
        <link rel="stylesheet" href="stylesheet_login.css">
        
        
</head>
<body>
    <img class="login-container" width="150px" src="icons/ReceiptLogger.png"/?>
     <br><br>
     <div class="login-container">
         <div class="login-box">
             <h2>Create User</h2>

<form action="register.php" method="post">
    <div class="textbox">
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" required><br>
    </div>
    
    <div class="textbox">
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required><br>
    </div>
    <button type="submit" name="register" class="btn">Register</button>
</form>
<br>

<!-- Display the message if set -->
<?php if (!empty($message)): ?>
    <div class="message">
        <?php echo $message; ?>
    </div>
<?php endif; ?>


<?php
session_start();

// Include the external database configuration file
require('/home/blkfarms/secure/db_config.php');

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize message variable
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    // Check if the username already exists in the database
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // If username already exists
        echo "Username already exists. Please choose a different one.";
    } else {
        // If username does not exist, hash the password and insert the new user into the database
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert the new user into the users table
        $sql = "INSERT INTO users (username, password, approved) VALUES ('$username', '$hashed_password', '0')";
        
        if ($conn->query($sql) === TRUE) {
            echo "User registered successfully! User will be disabled till approved by Administrator. To go back to the login screen <a href='login.php'>Click Here</a>";
        } else {
            echo "Error: " . $conn->error;
        }
    }
}
?>
</div>
</div>
</body>
<?php
$conn->close();
?>
