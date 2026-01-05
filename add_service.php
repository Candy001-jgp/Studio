<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_name = $_POST['service_name'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("INSERT INTO services (service_name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $service_name, $description);

    if ($stmt->execute()) {
        $success = "✅ Service added successfully!";
    } else {
        $error = "❌ Error: " . $stmt->error;
    }
}

// Fetch all services
$services = $conn->query("SELECT * FROM services ORDER BY service_id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Services - Higher Level Studios</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('IMG/DASHBOARD.JPG') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
        }

        .container {
            max-width: 700px;
            margin: 50px auto;
            padding: 40px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            color: #fff;
        }

        h2 {
            text-align: center;
            font-size: 28px;
            margin-bottom: 25px;
            color: #00ffe1;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 6px;
            font-weight: 600;
        }

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.85);
            color: #000;
            font-size: 15px;
            margin-bottom: 15px;
        }

        textarea {
            resize: vertical;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #27ae60, #3498db);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: linear-gradient(135deg, #2ecc71, #2980b9);
        }

        .success, .error {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: bold;
            text-align: center;
        }

        .success {
            background-color: rgba(46, 204, 113, 0.95);
            color: #fff;
        }

        .error {
            background-color: rgba(231, 76, 60, 0.95);
            color: #fff;
        }

        table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: #fff;
            font-size: 15px;
        }

        th, td {
            padding: 12px 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
        }

        th {
            background-color: rgba(0, 0, 0, 0.6);
            color: #00ffe1;
        }

        .action-btn {
            padding: 6px 10px;
            margin: 0 4px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            color: white;
            display: inline-block;
        }

        .edit-btn {
            background-color: #3498db;
        }

        .edit-btn:hover {
            background-color: #2980b9;
        }

        .delete-btn {
            background-color: #e74c3c;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        .back-link {
            text-align: center;
            margin-top: 25px;
        }

        .back-link a {
            color: #fff;
            text-decoration: underline;
        }

        .back-link a:hover {
            color: #00ffe1;
        }

        @media (max-width: 600px) {
            .container {
                margin: 30px 20px;
                padding: 30px;
            }

            table, thead, tbody, th, td, tr {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>➕ Add New Service</h2>

    <?php if (!empty($success)) echo "<div class='success'>$success</div>"; ?>
    <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>

    <form method="POST" action="">
        <label>Service Name:</label>
        <input type="text" name="service_name" required>

        <label>Description:</label>
        <textarea name="description" rows="3"></textarea>

        <button type="submit">Add Service</button>
    </form>

    <h2>📋 List of Services Offered</h2>
    <table>
        <tr>
            <th>#</th>
            <th>Service Name</th>
            <th>Description</th>
            <th>Action</th>
        </tr>
        <?php 
        $i = 1;
        while($row = $services->fetch_assoc()): ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['service_name']) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td>
                <a href="edit_service.php?id=<?= $row['service_id'] ?>" class="action-btn edit-btn">Edit</a>
                <a href="delete_service.php?id=<?= $row['service_id'] ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this service?');">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="back-link">
        <a href="dashboard.php">← Back to Dashboard</a>
    </div>
</div>

</body>
</html>
