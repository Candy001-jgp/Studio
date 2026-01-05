<?php
session_start();
include 'db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Check if service ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $service_id = $_GET['id'];

    // Optional: You can add a check here to ensure the service is not used in any bookings/logs before deleting

    // Prepare and execute delete query
    $stmt = $conn->prepare("DELETE FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $service_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Service deleted successfully!";
    } else {
        $_SESSION['error'] = "❌ Error deleting service: " . $stmt->error;
    }
} else {
    $_SESSION['error'] = "⚠️ Invalid service ID.";
}

// Redirect back to service list
header("Location: add_service.php");
exit();
?>
