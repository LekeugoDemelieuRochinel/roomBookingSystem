<?php
session_start();
include '../db_connect.php'; // Go up one level for db_connect.php

// Optional: Basic error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Admin Authentication Check ---
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== TRUE) {
    header("Location: ../login.php?error=admin_access_required");
    exit();
}

$message = ""; // For feedback messages (e.g., cancellation success/failure)
$all_bookings = []; // To store all fetched bookings

// --- Handle Booking Cancellation (Admin Action) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'cancel_booking_admin' && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);

    if ($booking_id <= 0) {
        $message = "<p class='message error'>Invalid booking ID.</p>";
    } else {
        // Fetch current booking details to prevent cancelling past bookings or already cancelled ones
        $stmt_check = $conn->prepare("SELECT status, start_time FROM bookings WHERE id = ?");
        $stmt_check->bind_param("i", $booking_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $booking_details = $result_check->fetch_assoc();
        $stmt_check->close();

        if ($booking_details) {
            $booking_start_datetime = new DateTime($booking_details['start_time']);
            $now = new DateTime();

            if ($booking_start_datetime < $now) {
                $message = "<p class='message error'>Cannot cancel a booking that has already passed.</p>";
            } elseif ($booking_details['status'] == 'cancelled') {
                $message = "<p class='message error'>This booking is already cancelled.</p>";
            } else {
                // Proceed with cancellation
                $stmt_cancel = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
                $stmt_cancel->bind_param("i", $booking_id);

                if ($stmt_cancel->execute()) {
                    $message = "<p class='message success'>Booking ID {$booking_id} cancelled successfully.</p>";
                } else {
                    $message = "<p class='message error'>Failed to cancel booking ID {$booking_id}: " . $stmt_cancel->error . "</p>";
                }
                $stmt_cancel->close();
            }
        } else {
            $message = "<p class='message error'>Booking not found.</p>";
        }
    }
}


// --- Fetch all bookings ---
// Join with 'users' and 'rooms' tables to get names instead of just IDs
$stmt = $conn->prepare("SELECT
                            b.id AS booking_id,
                            u.username,
                            r.room_name,
                            b.start_time,
                            b.end_time,
                            b.status
                        FROM
                            bookings b
                        JOIN
                            users u ON b.user_id = u.id
                        JOIN
                            rooms r ON b.room_id = r.id
                        ORDER BY
                            b.start_time DESC"); // Order by newest booking first
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $all_bookings[] = $row;
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
    <title>Manage All Bookings - Admin Panel</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f0f2f5; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #007bff; }
        .admin-nav { text-align: center; margin-bottom: 30px; }
        .admin-nav a { margin: 0 15px; color: #007bff; text-decoration: none; font-weight: bold; padding: 8px 12px; border: 1px solid #007bff; border-radius: 5px; transition: all 0.3s ease; }
        .admin-nav a:hover { background-color: #007bff; color: white; }
        .message { text-align: center; margin-top: 15px; padding: 10px; border-radius: 5px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #007bff; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .status-confirmed { color: #28a745; font-weight: bold; }
        .status-cancelled { color: #dc3545; font-weight: bold; }
        .actions button {
            background-color: #dc3545; /* Red for cancel */
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em;
            transition: background-color 0.3s ease;
        }
        .actions button:hover { background-color: #c82333; }
        .actions button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        .no-bookings { text-align: center; color: #6c757d; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage All Bookings</h1>

        <div class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="users.php">Manage Users</a>
            <a href="rooms.php">Manage Rooms</a>
            <a href="bookings.php">Manage All Bookings</a>
            <a href="../logout.php">Logout</a>
        </div>

        <?php echo $message; // Display status messages ?>

        <?php if (count($all_bookings) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Room</th>
                        <th>Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_bookings as $booking): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                            <td><?php echo htmlspecialchars($booking['username']); ?></td>
                            <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                            <td><?php echo (new DateTime($booking['start_time']))->format('Y-m-d'); ?></td>
                            <td><?php echo (new DateTime($booking['start_time']))->format('H:i'); ?></td>
                            <td><?php echo (new DateTime($booking['end_time']))->format('H:i'); ?></td>
                            <td><span class="status-<?php echo htmlspecialchars($booking['status']); ?>"><?php echo ucfirst(htmlspecialchars($booking['status'])); ?></span></td>
                            <td class="actions">
                                <?php
                                $booking_start_datetime = new DateTime($booking['start_time']);
                                $now = new DateTime();
                                $can_cancel = ($booking_start_datetime > $now && $booking['status'] == 'confirmed');
                                ?>
                                <form action="bookings.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this booking (ID: <?php echo $booking['booking_id']; ?>)?');">
                                    <input type="hidden" name="action" value="cancel_booking_admin">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                    <button type="submit" <?php echo $can_cancel ? '' : 'disabled'; ?>>Cancel</button>
                                </form>
                                </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-bookings">No bookings found in the system.</p>
        <?php endif; ?>

        <p style="text-align: center; margin-top: 30px;"><a href="index.php">← Back to Admin Dashboard</a></p>
    </div>
</body>
</html>