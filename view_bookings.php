<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$bookings = $conn->query("
    SELECT 
        b.booking_id,
        b.booking_date,
        b.booking_time,
        b.amount_paid,
        b.status,
        b.assigned_staff,
        c.client_name,
        u.full_name AS staff_name,
        GROUP_CONCAT(s.service_name SEPARATOR ', ') AS services
    FROM bookings b
    JOIN clients c ON b.client_id = c.client_id
    JOIN booking_services bs ON b.booking_id = bs.booking_id
    JOIN services s ON bs.service_id = s.service_id
    LEFT JOIN users u ON b.assigned_staff = u.user_id
    GROUP BY b.booking_id
    ORDER BY b.booking_date DESC, b.booking_time DESC
");

?>
<!DOCTYPE html>
<html>
<head>
    <title>View Bookings - Higher Level Studios</title>
    <style>
        body {
            background: #222;
            color: #fff;
            font-family: 'Segoe UI';
        }

        .container {
            max-width: 90%;
            margin: 50px auto;
        }

        h2 {
            text-align: center;
            color: #00ffe1;
        }

        #print-header {
            display: none;
            text-align: center;
            margin-bottom: 20px;
        }

        #print-header img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: contain;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
        }

        #print-header h2 {
            margin: 5px 0;
            font-size: 24px;
            color: black;
        }

        .print-btn {
            text-align: right;
            margin-bottom: 10px;
        }

        .print-btn button {
            padding: 6px 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }

        .print-btn button:hover {
            background-color: #2980b9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255,255,255,0.05);
        }

        th, td {
            padding: 12px;
            border: 1px solid #444;
            text-align: left;
        }

        th {
            background: #111;
            color: #00ffe1;
        }

        tr:nth-child(even) {
            background: #333;
        }

        a.button {
            color: #fff;
            padding: 6px 12px;
            background: green;
            text-decoration: none;
            border-radius: 4px;
        }

        .status {
            font-weight: bold;
            color: yellow;
        }

        .completed {
            color: #2ecc71;
        }

        .back-link {
            text-align: center;
            margin-top: 30px;
        }

        .back-link a {
            color: #00ffe1;
            text-decoration: underline;
        }

        /* PRINT STYLES */
        @media print {
            body {
                background: white;
                color: black;
            }

            .print-btn,
            .back-link,
            .button,
            .action-col {
                display: none !important;
            }

            #print-header {
                display: block;
            }

            table {
                background: white;
                color: black;
            }

            th {
                background-color: #00ffe1 !important;
                color: black !important;
            }

            .completed {
                color: green !important;
            }

            .status {
                color: orange !important;
            }
        }
    </style>
</head>
<body>

<!-- Print header only shown in print -->
<div id="print-header">
    <img src="IMG/logo.jpg" alt="Logo">
    <h2>HIGHER LEVEL STUDIOS & DESIGN 🎥</h2>
</div>

<div class="container">
    <h2>📋 Bookings</h2>

    <div class="print-btn">
        <button onclick="window.print()">🖨️ Print</button>
    </div>

    <table>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Time</th>
            <th>Client</th>
            <th>Services</th>
            <th>Amount</th>
            <th>Staff</th>
            <th>Status</th>
            <th class="action-col">Action</th>
        </tr>
        <?php $i = 1; while ($row = $bookings->fetch_assoc()): ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= $row['booking_date'] ?></td>
            <td><?= $row['booking_time'] ?></td>
            <td><?= htmlspecialchars($row['client_name']) ?></td>
            <td><?= htmlspecialchars($row['services']) ?></td>
            <td>KSh <?= number_format($row['amount_paid'], 2) ?></td>
            <td><?= htmlspecialchars($row['staff_name']) ?></td>
            <td class="status <?= $row['status'] === 'Completed' ? 'completed' : '' ?>">
                <?= $row['status'] ?>
            </td>
            <td class="action-col">
                <?php if ($row['status'] === 'Pending' && $row['assigned_staff'] == $user_id): ?>
                    <a href="mark_done.php?id=<?= $row['booking_id'] ?>" class="button">Mark Done</a>
                <?php elseif ($row['status'] === 'Completed'): ?>
                    ✅
                <?php else: ?>
                    🔒
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
