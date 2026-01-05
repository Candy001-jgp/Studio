<?php
session_start();
include 'db_connect.php';

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Check for service ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "⚠️ Invalid service ID.";
    header("Location: add_service.php");
    exit();
}

$service_id = $_GET['id'];

// Fetch service details
$stmt = $conn->prepare("SELECT * FROM services WHERE service_id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "❌ Service not found.";
    header("Location: add_service.php");
    exit();
}

$service = $result->fetch_assoc();

// Handle update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $service_name = $_POST['service_name'];
    $description = $_POST['description'];

    $update = $conn->prepare("UPDATE services SET service_name = ?, description = ? WHERE service_id = ?");
    $update->bind_param("ssi", $service_name, $description, $service_id);

    if ($update->execute()) {
        $_SESSION['success'] = "✅ Service updated successfully!";
        header("Location: add_service.php");
        exit();
    } else {
        $error = "❌ Update failed: " . $update->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Service</title>
    <style>
      body {
    background: url('IMG/INDEX.JPG') no-repeat center center fixed;
    background-size: cover;
    font-family: 'Segoe UI', sans-serif;
    color: #00ffe1;
    padding: 40px;
}

        .container {
            max-width: 600px;
            margin: auto;
            background: rgba(9, 192, 248, 0.05);
            padding: 30px;
            border-radius: 10px;
        }

        h2 {
            text-align: center;
            color: #00ffe1;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: none;
            margin-top: 5px;
            background: #333;
            color: #fff;
        }

        button {
            background: #00ffe1;
            color: #000;
            padding: 12px;
            margin-top: 20px;
            width: 100%;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #00bfa5;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #00ffe1;
            text-decoration: underline;
        }

        .error {
            background: #e74c3c;
            padding: 10px;
            margin: 10px 0;
            text-align: center;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>✏️ Edit Service</h2>

    <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>

    <form method="POST">
        <label>Service Name:</label>
        <input type="text" name="service_name" value="<?= htmlspecialchars($service['service_name']) ?>" required>

        <label>Description:</label>
        <textarea name="description" rows="3"><?= htmlspecialchars($service['description']) ?></textarea>

        <button type="submit">💾 Update Service</button>
    </form>

    <div class="back-link">
        <a href="add_service.php">← Back to Services</a>
    </div>
</div>

</body>
</html>
