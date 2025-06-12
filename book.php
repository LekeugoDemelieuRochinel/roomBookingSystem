<?php
session_start(); // Start the session at the very top for authentication

// Include the database connection
include 'db_connect.php';

// Optional: Basic error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Authentication Check ---
// Redirect to login page if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = ""; // To store feedback messages (e.g., booking success/failure)
$room_id = null;
$room = null;

// --- Fetch Room Details ---
// Check if a room_id was passed in the URL
if (isset($_GET['room_id']) && is_numeric($_GET['room_id'])) {
    $room_id = $_GET['room_id'];

    // Prepare and execute a query to get room details
    $stmt = $conn->prepare("SELECT id, room_name, capacity, equipment FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $room = $result->fetch_assoc();
    } else {
        $message = "<p style='color: red;'>Room not found.</p>";
    }
    $stmt->close();
} else {
    $message = "<p style='color: red;'>No room selected. Please go back to <a href='index.php'>Available Rooms</a>.</p>";
}

// --- Handle Booking Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $room_id && isset($_POST['booking_date']) && isset($_POST['start_time']) && isset($_POST['end_time'])) {
    // Sanitize input to prevent potential issues, even from hidden fields
    $booking_date = htmlspecialchars($_POST['booking_date']); // e.g., '2025-06-15'
    $start_time_str = htmlspecialchars($_POST['start_time']); // e.g., '09:00'
    $end_time_str = htmlspecialchars($_POST['end_time']);   // e.g., '10:00'

    // Combine date and time to create DATETIME strings for the database
    $start_datetime = $booking_date . ' ' . $start_time_str . ':00';
    $end_datetime = $booking_date . ' ' . $end_time_str . ':00';

    $user_id = $_SESSION['user_id']; // Get logged-in user's ID

    // Basic server-side validation: Ensure date and times are not empty
    if (empty($booking_date) || empty($start_time_str) || empty($end_time_str)) {
        $message = "<p style='color: red;'>Please select a date and time slot for your booking.</p>";
    } else {
        // --- Important: Implement availability check here before inserting! ---
        // This is crucial to prevent double-bookings.
        $is_available = true;

        // Check for overlaps: This query finds any existing bookings for the same room
        // where the requested slot (start_datetime, end_datetime) overlaps with an existing booking.
        // An overlap occurs if (Existing_Start < Requested_End) AND (Existing_End > Requested_Start)
        $overlap_stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?))");
        $overlap_stmt->bind_param("issss", $room_id, $end_datetime, $start_datetime, $end_datetime, $start_datetime);
        $overlap_stmt->execute();
        $overlap_result = $overlap_stmt->get_result();
        $overlap_count = $overlap_result->fetch_row()[0];
        $overlap_stmt->close();

        if ($overlap_count > 0) {
            $message = "<p style='color: red;'>This room is already booked for the selected time slot. Please choose another time.</p>";
            $is_available = false;
        }

        // Also check if the chosen end time is before or same as start time
        if (strtotime($start_datetime) >= strtotime($end_datetime)) {
            $message = "<p style='color: red;'>End time must be after start time.</p>";
            $is_available = false; // Mark as not available due to invalid time
        }

        // Further check: ensure booking is not in the past
        $now = new DateTime();
        $booking_start_obj = new DateTime($start_datetime);
        if ($booking_start_obj < $now) {
            $message = "<p style='color: red;'>You cannot book a time slot in the past.</p>";
            $is_available = false;
        }


        if ($is_available) {
            // Prepare and execute the INSERT query for the booking
            $stmt = $conn->prepare("INSERT INTO bookings (room_id, user_id, start_time, end_time, status) VALUES (?, ?, ?, ?, 'confirmed')");
            $stmt->bind_param("iiss", $room_id, $user_id, $start_datetime, $end_datetime);

            if ($stmt->execute()) {
                $message = "<p style='color: green;'>Room booked successfully!</p>";
                // Optional: You could redirect the user to their dashboard after a successful booking
                // header("Location: user_dashboard.php");
                // exit();
            } else {
                $message = "<p style='color: red;'>Booking failed: " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book <?php echo $room ? htmlspecialchars($room['room_name']) : 'Room'; ?></title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f0f2f5; color: #333; }
        .container { max-width: 960px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1, h2 { text-align: center; color: #007bff; }
        .room-details { background: #e9ecef; border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; margin-bottom: 20px; text-align: center; }
        .room-details h3 { margin-top: 0; color: #343a40; }
        .room-details p { margin-bottom: 5px; }
        .booking-section { margin-top: 30px; }
        /* Calendar specific styles (mostly handled by JS, but base styling helps) */
        #calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        #calendar-header button { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        #calendar-header button:hover { background-color: #0056b3; }
        #calendar-header h2 { margin: 0; color: #333; }

        #calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr); /* 7 days of the week */
            gap: 5px;
            margin-top: 20px;
            text-align: center;
        }
        .calendar-day-header { font-weight: bold; padding: 10px; background-color: #007bff; color: white; border-radius: 4px; }
        .calendar-day { padding: 10px; border: 1px solid #ddd; border-radius: 4px; background-color: #f8f9fa; }
        .calendar-day.current-month { background-color: #fff; cursor: pointer; }
        .calendar-day.current-month:hover { background-color: #e2e6ea; } /* Hover effect */
        .calendar-day.selected { background-color: #28a745; color: white; font-weight: bold; }

        /* Time slot styling */
        #time-slots-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 5px;
        }
        .time-slot {
            display: block;
            padding: 8px;
            text-align: center;
            background-color: #e0e0e0;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }
        .time-slot.available:hover { background-color: #b8daff; transform: translateY(-2px); }
        .time-slot.booked { background-color: #dc3545; color: white; cursor: not-allowed; text-decoration: line-through; opacity: 0.7; }
        .time-slot.selected-slot { background-color: #ffc107; color: #333; font-weight: bold; border-color: #ffc107; }

        /* Booking form styling (for the hidden fields) */
        .booking-form { margin-top: 20px; text-align: center; }
        .booking-form input[type="submit"] { background-color: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; transition: background-color 0.3s ease; }
        .booking-form input[type="submit"]:disabled { background-color: #cccccc; cursor: not-allowed; }
        .booking-form input[type="submit"]:hover:not(:disabled) { background-color: #0056b3; }

        .message { margin-top: 10px; text-align: center; }
        a.back-link { display: block; text-align: center; margin-top: 20px; color: #007bff; text-decoration: none; }
        a.back-link:hover { text-decoration: underline; }

        /* AUTH LINKS CSS ADDED BELOW */
        .auth-links { text-align: right; margin-bottom: 20px; }
        .auth-links a { margin-left: 15px; color: #007bff; text-decoration: none; font-weight: bold; }
        .auth-links a:hover { text-decoration: underline; }
        .welcome-text { font-weight: bold; margin-right: 10px; }
    </style>
</head>
<body>

    <div class="auth-links">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <a href="index.php">Browse Rooms</a>
            <a href="dashboard.php">My Bookings</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="register.php">Register</a>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </div>

    <div class="container">
        <?php if ($room): ?>
            <h1>Book: <?php echo htmlspecialchars($room['room_name']); ?></h1>
            <div class="room-details">
                <h3>Room Details</h3>
                <p>Capacity: <?php echo htmlspecialchars($room['capacity']); ?> students</p>
                <p>Equipment: <?php echo htmlspecialchars($room['equipment']); ?></p>
            </div>

            <?php echo $message; // Display any messages from booking attempt ?>

            <div class="booking-section">
                <h2>Select a Date and Time Slot</h2>

                <div id="calendar">
                    <div id="calendar-header"></div> <div id="calendar-grid"></div> </div>

                <h3>Available Time Slots for Selected Date:</h3>
                <div id="time-slots-container">
                    <p style="text-align: center; grid-column: 1 / -1;">Please select a date on the calendar.</p>
                </div>


                <form action="book.php?room_id=<?php echo $room_id; ?>" method="POST" class="booking-form">
                    <input type="hidden" id="booking_date" name="booking_date" required>
                    <input type="hidden" id="start_time" name="start_time" required>
                    <input type="hidden" id="end_time" name="end_time" required>
                    <input type="submit" value="Confirm Booking" id="confirmBookingBtn" disabled> </form>
            </div>

        <?php else: ?>
            <p class='no-rooms'>Room details could not be loaded. <?php echo $message; ?></p>
        <?php endif; ?>

        <a href="index.php" class="back-link">← Back to Available Rooms</a>
    </div>

    <script src="js/calendar.js"></script>
</body>
</html>
<?php $conn->close(); ?>