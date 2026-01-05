<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Higher Level Studios</title>
    <style>
        :root {
            --red: #e74c3c;
            --green: #27ae60;
            --blue: #2980b9;
            --bg-overlay: rgba(255, 255, 255, 0.88);
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('IMG/DASHBOARD.JPG') no-repeat center center fixed;
            background-size: cover;
        }

        .studio-banner {
            background: linear-gradient(to right, var(--blue), var(--green));
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 30px;
            font-weight: bold;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .studio-banner img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: contain;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
        }

        .header {
            background: rgba(44, 62, 80, 0.95);
            color: #fff;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logout-btn {
            background: var(--red);
            color: #fff;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        .main {
            padding: 30px 20px;
            max-width: 1200px;
            margin: 30px auto;
            background-color: var(--bg-overlay);
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }

        .cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: #ffffff;
            padding: 20px;
            flex: 1;
            min-width: 200px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h4 {
            color: #2c3e50;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .card p {
            font-size: 24px;
            font-weight: bold;
            color: var(--green);
        }

        .section-title {
            font-size: 22px;
            margin-bottom: 15px;
            border-left: 5px solid var(--blue);
            padding-left: 10px;
            color: #2c3e50;
        }

        ul.tool-links {
            list-style: none;
            padding: 0;
        }

        ul.tool-links li {
            margin: 10px 0;
        }

        ul.tool-links a {
            color: var(--blue);
            text-decoration: none;
            font-weight: 500;
        }

        ul.tool-links a:hover {
            text-decoration: underline;
        }

        .alert {
            background: #fff3cd;
            color: #856404;
            padding: 12px 16px;
            border: 1px solid #ffeeba;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th, table td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }

        table th {
            background-color: var(--blue);
            color: white;
        }

        @media (max-width: 768px) {
            .cards {
                flex-direction: column;
            }

            .studio-banner {
                flex-direction: column;
                text-align: center;
            }

            .studio-banner img {
                width: 90px;
                height: 90px;
            }

            .studio-banner span {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<div class="studio-banner">
    <img src="IMG/logo.jpg" alt="Higher Level Studios Logo">
    <span>HIGHER LEVEL STUDIOS & DESIGN 🎥</span>
</div>

<div class="header">
    <h3>Welcome, <?= htmlspecialchars($full_name) ?> (<?= ucfirst($role) ?>)</h3>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="main">
<?php if ($role == 'admin'): ?>
    <?php
        $total_clients = $conn->query("SELECT COUNT(*) FROM clients")->fetch_row()[0];
        $total_bookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0];
        $total_work_logs = $conn->query("SELECT COUNT(*) FROM daily_logs")->fetch_row()[0];


        $pending_bookings = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'Pending'")->fetch_row()[0];
        $pending_logs = $conn->query("SELECT COUNT(*) FROM daily_logs WHERE status = 'Pending'")->fetch_row()[0];
        $total_pending_jobs = $pending_bookings + $pending_logs;

        $staff_pending = $conn->query("
            SELECT u.full_name,
                (SELECT COUNT(*) FROM bookings b WHERE b.assigned_staff = u.user_id AND b.status = 'Pending') AS pending_bookings,
                (SELECT COUNT(*) FROM daily_logs d WHERE d.staff_id = u.user_id AND d.status = 'Pending') AS pending_logs
            FROM users u WHERE u.role = 'staff'
        ");
    ?>

    <h3 class="section-title">📊 Dashboard Overview</h3>
    <div class="cards">
        <div class="card"><h4>Total Clients</h4><p><?= $total_clients ?></p></div>
        <div class="card"><h4>Total Bookings</h4><p><?= $total_bookings ?></p></div>
        <div class="card"><h4>Daily Work Logs</h4><p><?= $total_work_logs ?></p></div>
    </div>

    <?php if ($total_pending_jobs > 0): ?>
        <div class="alert">
            ⚠️ You have <strong><?= $total_pending_jobs ?></strong> pending job<?= $total_pending_jobs > 1 ? 's' : '' ?>:
            <?= $pending_bookings ?> booking<?= $pending_bookings != 1 ? 's' : '' ?>,
            <?= $pending_logs ?> log<?= $pending_logs != 1 ? 's' : '' ?>.
        </div>
    <?php endif; ?>

    <h3 class="section-title">👥 Staff with Pending Jobs</h3>
    <table>
        <thead>
            <tr>
                <th>Staff Name</th>
                <th>Pending Bookings</th>
                <th>Pending Logs</th>
                <th>Total Pending</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($staff = $staff_pending->fetch_assoc()):
                $total = $staff['pending_bookings'] + $staff['pending_logs'];
                if ($total == 0) continue;
            ?>
            <tr>
                <td><?= htmlspecialchars($staff['full_name']) ?></td>
                <td><?= $staff['pending_bookings'] ?></td>
                <td><?= $staff['pending_logs'] ?></td>
                <td style="font-weight:bold; color:red;"><?= $total ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h3 class="section-title">🔧 Admin Tools</h3>
    <ul class="tool-links">
        <li><a href="add_user.php">Manage Users</a></li>
        <li><a href="add_booking.php">Add Booking</a></li>
        <li><a href="add_daily_work.php">Log Daily Work</a></li>
        <li><a href="view_daily_work.php">View Work Logs</a></li>
        <li><a href="view_bookings.php">All Bookings</a></li>
        <li><a href="add_service.php">Manage Services</a></li>
        <li><a href="view_payments.php">View Payments</a></li>
        <li><a href="expenses.php">Expenses</a></li>
        <li><a href="reports.php">Reports</a></li>
    </ul>

<?php else: ?>
    <?php
        $my_work_logs = $conn->query("SELECT COUNT(*) FROM daily_logs WHERE staff_id = $user_id")->fetch_row()[0];
        $my_bookings = $conn->query("SELECT COUNT(*) FROM bookings WHERE assigned_staff = $user_id")->fetch_row()[0];
        $pending_logs = $conn->query("SELECT COUNT(*) FROM daily_logs WHERE staff_id = $user_id AND status = 'Pending'")->fetch_row()[0];
        $pending_bookings = $conn->query("SELECT COUNT(*) FROM bookings WHERE assigned_staff = $user_id AND status = 'Pending'")->fetch_row()[0];
        $completed_logs = $conn->query("SELECT COUNT(*) FROM daily_logs WHERE staff_id = $user_id AND status = 'Completed'")->fetch_row()[0];
        $total_pending = $pending_logs + $pending_bookings;
    ?>

    <?php if ($total_pending > 0): ?>
        <div class="alert">
            ⚠️ Alert <?= $total_pending ?> pending job<?= $total_pending > 1 ? 's' : '' ?>:
            <?= $pending_bookings ?> booking<?= $pending_bookings != 1 ? 's' : '' ?>, 
            <?= $pending_logs ?> log<?= $pending_logs != 1 ? 's' : '' ?>. Please address them.
        </div>
    <?php endif; ?>

    <h3 class="section-title">📋 My Dashboard</h3>
    <div class="cards">
        <div class="card"><h4>My Work Logs</h4><p><?= $my_work_logs ?></p></div>
        <div class="card"><h4>My Bookings</h4><p><?= $my_bookings ?></p></div>
        <div class="card"><h4>Completed Logs</h4><p style="color: green;"><?= $completed_logs ?></p></div>
        <div class="card"><h4>Pending</h4><p style="color: red;"><?= $total_pending ?></p></div>
    </div>

    <h3 class="section-title">🧰 Staff Tools</h3>
    <ul class="tool-links">
        <li><a href="add_booking.php">Add Booking</a></li>
        <li><a href="add_daily_work.php">Log Daily Work</a></li>
        <li><a href="view_daily_work.php">Work Logs</a></li>
        <li><a href="view_bookings.php">Bookings</a></li>
    </ul>
<?php endif; ?>
</div>

</body>
</html>
