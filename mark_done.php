<?php
session_start();
include 'db_connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $booking_id = intval($_GET['id']);

    // Optional: Ensure only the assigned staff/admin can mark it done
    $user_id = $_SESSION['user_id'];
    $check = $conn->prepare("SELECT assigned_staff FROM bookings WHERE booking_id = ?");
    $check->bind_param("i", $booking_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $assigned = $result->fetch_assoc()['assigned_staff'];
        if ($assigned == $user_id || $_SESSION['role'] === 'admin') {
            $update = $conn->prepare("UPDATE bookings SET status = 'Completed' WHERE booking_id = ?");
            $update->bind_param("i", $booking_id);
            $update->execute();
        }
    }
}

header("Location: view_bookings.php");
exit();
?>
