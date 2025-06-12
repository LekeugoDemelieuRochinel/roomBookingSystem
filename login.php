<?php
session_start(); // Start the session at the very top

include 'db_connect.php'; // Database connection

$message = ""; // To store success/error messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $message = "<p style='color: red;'>Please enter both username and password.</p>";
    } else {
        // Prepare the SQL statement to find the user
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Verify the password using password_verify()
            if (password_verify($password, $user['password'])) {
                // Password is correct, create session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                // Redirect to the main page or a user dashboard
                header("Location: index.php"); // Or wherever you want to redirect
                exit(); // Always exit after header()
            } else {
                $message = "<p style='color: red;'>Incorrect password.</p>";
            }
        } else {
            $message = "<p style='color: red;'>Incorrect username.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f0f2f5; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #007bff; }
        form label { display: block; margin-bottom: 5px; font-weight: bold; }
        form input[type="text"],
        form input[type="password"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        form input[type="submit"] { background-color: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; transition: background-color 0.3s ease; }
        form input[type="submit"]:hover { background-color: #0056b3; }
        .message { margin-top: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php echo $message; ?>
        <form action="login.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" value="Login">
        </form>
        <p class="message">Don't have an account? <a href="register.php">Register</a></p>
    </div>
</body>
</html>