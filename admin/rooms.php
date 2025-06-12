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

$message = ""; // For feedback messages (add/edit/delete success/failure)
$rooms = []; // To store fetched rooms

// --- Fetch all rooms ---
$sql = "SELECT id, room_name, capacity, equipment FROM rooms ORDER BY room_name ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - Admin Panel</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f0f2f5; color: #333; }
        .container { max-width: 960px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #007bff; }
        .admin-nav { text-align: center; margin-bottom: 30px; }
        .admin-nav a { margin: 0 15px; color: #007bff; text-decoration: none; font-weight: bold; padding: 8px 12px; border: 1px solid #007bff; border-radius: 5px; transition: all 0.3s ease; }
        .admin-nav a:hover { background-color: #007bff; color: white; }
        .message { text-align: center; margin-top: 15px; padding: 10px; border-radius: 5px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .add-room-button-container { text-align: right; margin-bottom: 20px; }
        .add-room-button { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; }
        .add-room-button:hover { background-color: #218838; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #007bff; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .actions a { margin-right: 5px; text-decoration: none; color: #007bff; }
        .actions button {
            background: none;
            border: none;
            color: #dc3545; /* Red for delete */
            cursor: pointer;
            font-size: 1em;
            padding: 0;
            margin: 0;
        }
        .actions button:hover { text-decoration: underline; }
        .no-rooms { text-align: center; color: #6c757d; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Study Rooms</h1>

        <div class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="users.php">Manage Users</a>
            <a href="rooms.php">Manage Rooms</a>
            <a href="bookings.php">Manage All Bookings</a>
            <a href="../logout.php">Logout</a>
        </div>

        <?php
        // Display status message from session if available
        if (isset($_SESSION['admin_status_message'])) {
            $msg_class = strpos($_SESSION['admin_status_message'], 'successfully') !== false ? 'success' : 'error';
            echo "<p class='message {$msg_class}'>" . htmlspecialchars($_SESSION['admin_status_message']) . "</p>";
            unset($_SESSION['admin_status_message']); // Clear the message
        }
        ?>

        <div class="add-room-button-container">
            <a href="rooms.php?action=add" class="add-room-button">Add New Room</a>
        </div>

        <?php if (count($rooms) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Room Name</th>
                        <th>Capacity</th>
                        <th>Equipment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($room['id']); ?></td>
                            <td><?php echo htmlspecialchars($room['room_name']); ?></td>
                            <td><?php echo htmlspecialchars($room['capacity']); ?></td>
                            <td><?php echo htmlspecialchars($room['equipment']); ?></td>
                            <td class="actions">
                                <a href="rooms.php?action=edit&id=<?php echo $room['id']; ?>">Edit</a>
                                <form action="rooms.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete room \'<?php echo htmlspecialchars($room['room_name']); ?>\'? This cannot be undone and will affect associated bookings!');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $room['id']; ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-rooms">No study rooms found. Click "Add New Room" to get started.</p>
        <?php endif; ?>

        <p style="text-align: center; margin-top: 30px;"><a href="index.php">← Back to Admin Dashboard</a></p>
    </div>
</body>
</html>