<?php
include 'db_connect.php'; // Database connection

$message = ""; // To store success/error messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password']; // Don't sanitize the raw password

    // Basic validation (you'll want more robust validation in a real app)
    if (empty($username) || empty($email) || empty($password)) {
        $message = "<p style='color: red;'>Please fill in all fields.</p>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<p style='color: red;'>Invalid email format.</p>";
    } else {
        // Hash the password securely using bcrypt
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare the SQL statement
        $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $email);

        if ($stmt->execute()) {
            $message = "<p style='color: green;'>Registration successful! <a href='login.php'>Login here</a></p>";
        } else {
             // Check for duplicate username or email errors
            if (strpos($conn->error, 'Duplicate entry') !== false) {
                $message = "<p style='color: red;'>Username or email already exists.</p>";
            } else {
                $message = "<p style='color: red;'>Registration failed: " . $stmt->error . "</p>";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f0f2f5; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #007bff; }
        form label { display: block; margin-bottom: 5px; font-weight: bold; }
        form input[type="text"],
        form input[type="email"],
        form input[type="password"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        form input[type="submit"] { background-color: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; transition: background-color 0.3s ease; }
        form input[type="submit"]:hover { background-color: #0056b3; }
        .message { margin-top: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Register</h2>
        <?php echo $message; ?>
        <form action="register.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" value="Register">
        </form>
        <p class="message">Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>
</html>