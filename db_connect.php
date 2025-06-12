<?php

$servername = "localhost";
$username = "root";
$password = "demelieu2005"; // <--- IMPORTANT: CHANGE THIS to your actual root password!
$dbname = "room_booking_db"; // this is the name of our databse

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Uncomment for testing:
// echo "Connected to Room Booking DB successfully!";
?>