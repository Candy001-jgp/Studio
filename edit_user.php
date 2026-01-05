<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: add_user.php");
    exit();
}

$user_id = $_GET['id'];

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    $new_password = $_POST['password'];

    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET full_name = ?, username = ?, role = ?, password = ? WHERE user_id = ?");
        $update->bind_param("ssssi", $full_name, $username, $role, $hashed_password, $user_id);
    } else {
        $update = $conn->prepare("UPDATE users SET full_name = ?, username = ?, role = ? WHERE user_id = ?");
        $update->bind_param("sssi", $full_name, $username, $role, $user_id);
    }

    if ($update->execute()) {
        $success = "✅ User updated successfully!";
        header("refresh:2;url=add_user.php");
    } else {
        $error = "❌ Update failed: " . $update->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User - Higher Level Studios</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('IMG/INDEX.JPG') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
            margin: 0;
        }

        .container {
            max-width: 600px;
            margin: 60px auto;
            padding: 40px;
            background: rgba(255,255,255,0.12);
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
            backdrop-filter: blur(12px);
        }

        h2 {
            text-align: center;
            color: #00ffe1;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: 600;
        }

        input, select {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            margin-bottom: 20px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background: rgba(255, 255, 255, 0.85);
            color: #000;
            font-size: 15px;
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
    </style>
</head>
<body>

<div class="container">
    <h2>✏️ Edit User</h2>

    <?php if (!empty($success)) echo "<div class='success'>$success</div>"; ?>
    <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>

    <form method="POST">
        <label>Full Name:</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>

        <label>Username:</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

        <label>Role:</label>
        <select name="role" required>
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
        </select>

        <label>New Password (leave blank to keep current):</label>
        <input type="password" name="password">

        <button type="submit">Update User</button>
    </form>

    <div class="back-link">
        <a href="add_user.php">← Back to Manage Users</a>
    </div>
</div>

</body>
</html>
