<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $row = $res->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['full_name'] = $row['full_name'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid credentials. Please try again.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Higher Level Studios</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: url('IMG/INDEX.JPG') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            color: #fff;
        }

        .overlay {
            background-color: rgba(0, 0, 0, 0.6);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .branding {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 25px;
            flex-wrap: wrap;
        }

        .branding img.logo {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            background:transparent;
            padding: 10px;
            object-fit: contain;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
        }

        .branding h1 {
            font-size: 42px;
            margin: 0;
            color: #00ffe1;
        }

        .header p {
            font-size: 18px;
            color: #ccc;
            margin-top: 15px;
            line-height: 1.6;
        }

        .login-container {
            max-width: 450px;
            margin: auto;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 25px rgba(0,0,0,0.4);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #fff;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 12px 0;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.85);
            color: #000;
        }

        input::placeholder {
            color: #777;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #27ae60;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            font-size: 16px;
        }

        button:hover {
            background: #218c53;
        }

        .error {
            color: #ff4d4d;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }

        .forgot {
            text-align: right;
            font-size: 13px;
            margin-top: 8px;
        }

        .forgot a {
            color: #f1f1f1;
            text-decoration: none;
        }

        .forgot a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .branding {
                flex-direction: column;
            }

            .branding h1 {
                font-size: 28px;
                text-align: center;
            }

            .branding img.logo {
                width: 100px;
                height: 100px;
            }

            .header p {
                font-size: 15px;
            }

            .login-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="overlay">
    <div class="header">
        <div class="branding">
            <img src="IMG/logo.jpg" alt="Higher Level Studios Logo" class="logo">
            <h1>Higher Level Studios & Design</h1>
        </div>
        <p>Where Creativity Meets Excellence in Photography, Videography, Photo Mounting,<br>
            Makeup, Fashion Designing, Outfits Hiring & Clothes Branding</p>
    </div>

    <div class="login-container">
        <h2>Login to Your Account</h2>

        <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>

        <form method="POST">
            <label>Username:</label>
            <input type="text" name="username" required placeholder="Enter your username">

            <label>Password:</label>
            <input type="password" name="password" required placeholder="Enter your password">

            <button type="submit">Login</button>

        </form>
    </div>
</div>

</body>
</html>
