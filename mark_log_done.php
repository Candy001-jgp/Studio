<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $log_id = $_GET['id'];

    $stmt = $conn->prepare("UPDATE daily_logs SET status = 'Completed' WHERE log_id = ?");
    $stmt->bind_param("i", $log_id);
    $stmt->execute();

    header("Location: view_daily_work.php");
    exit();
} else {
    echo "Invalid request.";
}
?>
