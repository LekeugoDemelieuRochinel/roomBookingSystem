<?php
session_start();
header('Content-Type: application/json'); // Tell the browser to expect JSON

include 'db_connect.php';

// --- Authentication Check ---
// This endpoint should also be protected
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated.']);
    exit();
}

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';

$response = []; // Array to hold the time slots

if ($room_id > 0 && !empty($date)) {
    // Define your standard booking time slots (e.g., hourly from 9 AM to 5 PM)
    $working_hours_start = 9; // 9 AM
    $working_hours_end = 17;  // 5 PM (meaning last bookable slot starts at 5 PM, ends 6 PM)
    $slot_duration_minutes = 60; // 1 hour slots

    $all_slots = [];

    // Generate all possible time slots for the day
    for ($hour = $working_hours_start; $hour <= $working_hours_end; $hour++) {
        $start_time_obj = new DateTime("{$date} {$hour}:00:00");
        $end_time_obj = clone $start_time_obj;
        $end_time_obj->modify("+{$slot_duration_minutes} minutes");

        // Ensure the end time doesn't go past the defined end of working hours (e.g. 6 PM for a 5 PM-6 PM slot)
        if ($end_time_obj->format('H') > ($working_hours_end + 1)) { // +1 because 5 PM slot ends at 6 PM
            continue;
        }

        $all_slots[] = [
            'start_time' => $start_time_obj->format('H:i'),
            'end_time' => $end_time_obj->format('H:i'),
            'is_booked' => false // Default to not booked
        ];
    }

    // Fetch existing bookings for this room and date
    $booked_slots = [];
    $stmt = $conn->prepare("SELECT start_time, end_time, status FROM bookings WHERE room_id = ? AND DATE(start_time) = ?");
    $stmt->bind_param("is", $room_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if ($row['status'] != "cancelled") {
            $booked_slots[] = [
                'start_time' => (new DateTime($row['start_time']))->format('H:i'),
                'end_time' => (new DateTime($row['end_time']))->format('H:i')
            ];
        }
    }
    $stmt->close();

    // Compare all_slots with booked_slots to mark availability
    foreach ($all_slots as &$slot) { // Use & to modify the original array elements
        $slot_start_dt = new DateTime("{$date} {$slot['start_time']}:00");
        $slot_end_dt = new DateTime("{$date} {$slot['end_time']}:00");

        foreach ($booked_slots as $booked_slot) {
            $booked_start_dt = new DateTime("{$date} {$booked_slot['start_time']}:00");
            $booked_end_dt = new DateTime("{$date} {$booked_slot['end_time']}:00");

            // Check for overlap: (StartA < EndB) && (EndA > StartB)
            // This is a common algorithm for checking if two time intervals overlap
            if ($slot_start_dt < $booked_end_dt && $slot_end_dt > $booked_start_dt) {
                $slot['is_booked'] = true;
                break; // No need to check this slot against other booked slots once an overlap is found
            }
        }
    }
    unset($slot); // Unset the reference

    $response = $all_slots;

} else {
    $response = ['error' => 'Invalid room ID or date.'];
}

$conn->close();
echo json_encode($response);
?>