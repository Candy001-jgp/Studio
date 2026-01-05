<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Handle deletion
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $conn->query("DELETE FROM users WHERE user_id = $delete_id AND role = 'staff'");
    header("Location: add_user.php");
    exit();
}

// Handle user registration
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $check = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {
        $error = "Username already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (full_name, username, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $full_name, $username, $password, $role);

        if ($stmt->execute()) {
            $success = "✅ User added successfully!";
        } else {
            $error = "❌ Error: " . $stmt->error;
        }
    }
}

// Fetch all users
$users = $conn->query("SELECT * FROM users ORDER BY role, full_name");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users - Higher Level Studios</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('IMG/INDEX.JPG') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
        }

        .container {
            max-width: 800px;
            margin: 60px auto;
            padding: 40px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
            backdrop-filter: blur(12px);
            color: #fff;
        }

        h2, h3 {
            text-align: center;
            color: #00ffe1;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: 600;
        }

        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            margin-bottom: 20px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background: rgba(255, 255, 255, 0.85);
            color: #000;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #27ae60, #3498db);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
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

        .success { background-color: rgba(46, 204, 113, 0.95); }
        .error { background-color: rgba(231, 76, 60, 0.95); }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            background: rgba(255,255,255,0.08);
            color: #fff;
        }

        th, td {
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        th {
            background-color: rgba(0, 0, 0, 0.6);
            color: #00ffe1;
        }

        tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .actions a {
            color: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 5px;
            font-size: 14px;
        }

        .edit-btn {
            background-color: #3498db;
        }

        .delete-btn {
            background-color: #e74c3c;
        }

        .actions a:hover {
            opacity: 0.8;
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

            table {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>👥 Manage Users</h2>

    <?php if (!empty($success)) echo "<div class='success'>$success</div>"; ?>
    <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>

    <form method="POST">
        <label>Full Name:</label>
        <input type="text" name="full_name" required>

        <label>Username:</label>
        <input type="text" name="username" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <label>Role:</label>
        <select name="role" required>
            <option value="">-- Select Role --</option>
            <option value="admin">Admin</option>
            <option value="staff">Staff</option>
        </select>

        <button type="submit">Add User</button>
    </form>

    <h3>📋 List of Users</h3>
    <table>
        <tr>
            <th>#</th>
            <th>Full Name</th>
            <th>Username</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
        <?php 
        $i = 1;
        while ($row = $users->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= ucfirst($row['role']) ?></td>
                <td class="actions">
                    <a href="edit_user.php?id=<?= $row['user_id'] ?>" class="edit-btn">Edit</a>
                    <?php if ($row['role'] == 'staff'): ?>
                        <a href="add_user.php?delete=<?= $row['user_id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this staff member?')">Delete</a>
                    <?php endif; ?>
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
