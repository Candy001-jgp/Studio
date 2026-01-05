<?php
$host = "sql303.infinityfree.com"; // use the exact host from InfinityFree
$user = "if0_39450617";     // your InfinityFree username
$password = "Candycastro001"; // use the password they gave you
$database = "if0_39450617_Studio"; // your full DB name

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
