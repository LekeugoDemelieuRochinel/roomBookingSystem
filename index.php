<?php
session_start(); 
include 'db_connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Room Booking</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f0f2f5; color: #333; }
        .container { max-width: 960px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #007bff; }
        .room-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 30px; }
        .room-card { background: #e9ecef; border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; }
        .room-card h3 { margin-top: 0; color: #343a40; }
        .room-card p { margin-bottom: 5px; font-size: 0.95em; }
        .room-card .capacity { font-weight: bold; color: #28a745; }
        .room-card .equipment { font-style: italic; color: #6c757d; }
        .no-rooms { text-align: center; color: #dc3545; }
        .auth-links { text-align: right; margin-bottom: 20px; } /* Changed to right align */
        .auth-links a { margin-left: 15px; color: #007bff; text-decoration: none; font-weight: bold; } /* Adjusted margin */
        .auth-links a:hover { text-decoration: underline; }
        .welcome-text { font-weight: bold; margin-right: 10px; } /* Added for welcome */
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-links">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="dashboard.php">My Bookings</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="register.php">Register</a>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </div>

        <h1>Available Study Rooms</h1>
        <div class="room-list">
            <?php
            // Fetch rooms from the database
            $sql = "SELECT id, room_name, capacity, equipment FROM rooms ORDER BY room_name ASC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                // Output data of each room
                while($row = $result->fetch_assoc()) {
                    echo "<div class='room-card'>";
                    echo "<h3>" . htmlspecialchars($row["room_name"]) . "</h3>";
                    echo "<p>Capacity: <span class='capacity'>" . htmlspecialchars($row["capacity"]) . "</span> students</p>";
                    echo "<p class='equipment'>Equipment: " . htmlspecialchars($row["equipment"]) . "</p>";
                    // Only show "Book This Room" if logged in
                    if (isset($_SESSION['user_id'])) {
                        echo "<p><a href='book.php?room_id=" . $row['id'] . "'>Book This Room</a></p>";
                    } else {
                        echo "<p>Login to book this room.</p>";
                    }
                    echo "</div>";
                }
            } else {
                echo "<p class='no-rooms'>No study rooms found.</p>";
            }

            $conn->close(); // Close the database connection
            ?>
        </div>
    </div>
</body>
</html>