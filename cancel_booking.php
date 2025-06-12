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
$message = ""; // To store success/error message

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']); // Sanitize input

    if ($booking_id <= 0) {
        $message = "Invalid booking ID.";
    } else {
        // First, verify that this booking belongs to the logged-in user
        // and that its status is 'confirmed' and it's in the future.
        $verify_stmt = $conn->prepare("SELECT id, start_time, room_id, status FROM bookings WHERE id = ? AND user_id = ?");
        $verify_stmt->bind_param("ii", $booking_id, $user_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        $booking_to_cancel = $verify_result->fetch_assoc();
        $verify_stmt->close();

        if ($booking_to_cancel) {
            $booking_start_datetime = new DateTime($booking_to_cancel['start_time']);
            $now = new DateTime();

            if ($booking_start_datetime < $now) {
                $message = "Cannot cancel a booking that has already passed.";
            } elseif ($booking_to_cancel['status'] != 'confirmed') {
                $message = "This booking cannot be cancelled (current status: " . htmlspecialchars($booking_to_cancel['status']) . ").";
            } else {
                // Update the booking status to 'cancelled'
                $update_stmt = $conn->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ? AND room_id = ?");
                $update_stmt->bind_param("iii", $booking_id, $user_id, $booking_to_cancel['room_id']);

                if ($update_stmt->execute()) {
                    $message = "Booking cancelled successfully.";
                } else {
                    $message = "Failed to cancel booking: " . $update_stmt->error;
                }
                $update_stmt->close();
            }
        } else {
            $message = "Booking not found or you do not have permission to cancel it.";
        }
    }
} else {
    $message = "Invalid request.";
}

$conn->close();

// Store the message in a session variable and redirect back to the dashboard
$_SESSION['status_message'] = $message;
header("Location: dashboard.php");
exit();
?>