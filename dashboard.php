<?php
session_start(); // Start the session

include 'db_connect.php'; // Include database connection

// --- Authentication Check ---
// Redirect to login page if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']); // Get username from session for greeting

$bookings = []; // Array to store fetched bookings

// Fetch bookings for the logged-in user
// Join with 'rooms' table to get room names
$stmt = $conn->prepare("SELECT
                            b.id AS booking_id,
                            r.room_name,
                            r.capacity,
                            r.equipment,
                            b.start_time,
                            b.end_time,
                            b.status
                        FROM
                            bookings b
                        JOIN
                            rooms r ON b.room_id = r.id
                        WHERE
                            b.user_id = ?
                        ORDER BY
                            b.start_time DESC"); // Order by newest booking first
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Study Room Booking</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f0f2f5; color: #333; }
        .container { max-width: 960px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #007bff; }
        .welcome-message { text-align: center; margin-bottom: 20px; font-size: 1.1em; }
        .navbar { text-align: center; margin-bottom: 30px; }
        .navbar a { margin: 0 15px; color: #007bff; text-decoration: none; font-weight: bold; }
        .navbar a:hover { text-decoration: underline; }
        .booking-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .booking-card { background: #e9ecef; border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; }
        .booking-card h3 { margin-top: 0; color: #343a40; }
        .booking-card p { margin-bottom: 5px; font-size: 0.95em; }
        .booking-card .status-confirmed { color: #28a745; font-weight: bold; }
        .booking-card .status-cancelled { color: #dc3545; font-weight: bold; }
        .no-bookings { text-align: center; color: #6c757d; margin-top: 30px; }
      /* Add this to dashboard.php's <style> block */
    .cancel-button {
    background-color: #dc3545; /* Red color for cancel */
    color: white;
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9em;
    margin-top: 10px;
    transition: background-color 0.3s ease;
}
.cancel-button:hover {
    background-color: #c82333;
}

.message {
    text-align: center;
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 5px;
    background-color: #d4edda; /* Light green for success */
    color: #155724; /* Dark green text */
    border: 1px solid #c3e6cb;
}
/* You might want different colors for error messages */
.message.error {
    background-color: #f8d7da; /* Light red for error */
    color: #721c24; /* Dark red text */
    border: 1px solid #f5c6cb;
}
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome, <?php echo $username; ?>!</h1>
        <p class="welcome-message">Here are your current and past study room bookings.</p>

        <div class="navbar">
            <a href="index.php">Browse Rooms</a>
            <a href="dashboard.php">My Bookings</a>
            <a href="logout.php">Logout</a>
        </div>
        <h2>Your Bookings</h2>

        <?php
        // Display status message from session if available
        if (isset($_SESSION['status_message'])) {
            echo "<p class='message'>" . htmlspecialchars($_SESSION['status_message']) . "</p>";
            unset($_SESSION['status_message']); // Clear the message after displaying it
        }
        ?>

        <?php if (count($bookings) > 0): ?>
            <div class="booking-list">
                <?php foreach ($bookings as $booking): ?>
                    <div class="booking-card">
                        <h3><?php echo htmlspecialchars($booking['room_name']); ?></h3>
                        <p><strong>Date:</strong> <?php echo (new DateTime($booking['start_time']))->format('Y-m-d'); ?></p>
                        <p><strong>Time:</strong> <?php echo (new DateTime($booking['start_time']))->format('H:i') . ' - ' . (new DateTime($booking['end_time']))->format('H:i'); ?></p>
                        <p><strong>Status:</strong> <span class="status-<?php echo htmlspecialchars($booking['status']); ?>"><?php echo ucfirst(htmlspecialchars($booking['status'])); ?></span></p>
                        <p>Capacity: <?php echo htmlspecialchars($booking['capacity']); ?></p>
                        <p>Equipment: <?php echo htmlspecialchars($booking['equipment']); ?></p>

                        <?php
                        // Check if the booking is in the future AND is confirmed (not already cancelled)
                        $booking_start_datetime = new DateTime($booking['start_time']);
                        $now = new DateTime();
                        if ($booking_start_datetime > $now && $booking['status'] == 'confirmed'):
                        ?>
                            <form action="cancel_booking.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                <button type="submit" class="cancel-button">Cancel Booking</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-bookings">You have no bookings yet. Go <a href="index.php">browse available rooms</a>!</p>
        <?php endif; ?>
    </div>
</body>
</html>