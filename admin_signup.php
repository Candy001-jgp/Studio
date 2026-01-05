<?php
include 'db_connect.php';
// Only allow signup if no admin exists
$check_admin = $conn->query("SELECT * FROM users WHERE role = 'admin'");
if ($check_admin->num_rows > 0) {
    header("Location: index.php");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // secure hashing
    $role = 'admin';

    // Check if username already exists
    $check = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        $error = "Username already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (full_name, username, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $full_name, $username, $password, $role);

        if ($stmt->execute()) {
            $success = "Admin account created successfully! You can now login.";
        } else {
            $error = "Error creating account: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Signup - Higher Level Studios</title>
    <style>
        body { font-family: Arial; background: #f2f2f2; padding: 40px; }
        form { background: #fff; padding: 20px; border-radius: 6px; width: 400px; margin: auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin: 8px 0; }
        button { padding: 10px 15px; background: #3498db; color: white; border: none; border-radius: 4px; }
        .msg { padding: 10px; margin-top: 10px; }
        .error { background: #f8d7da; color: #721c24; }
        .success { background: #d4edda; color: #155724; }
    </style>
</head>
<body>

<h2 style="text-align:center;">Create Admin Account</h2>

<form method="POST" action="">
    <label>Full Name</label>
    <input type="text" name="full_name" required>

    <label>Username</label>
    <input type="text" name="username" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <button type="submit">Create Admin</button>

    <?php if (!empty($success)) echo "<div class='msg success'>$success</div>"; ?>
    <?php if (!empty($error)) echo "<div class='msg error'>$error</div>"; ?>
</form>

<p style="text-align:center;"><a href="index.php">← Back to Login</a></p>

</body>
</html>
