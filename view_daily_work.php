<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$logs = $conn->query("
    SELECT 
        l.log_id,
        l.log_date,
        l.price,
        l.status,
        l.description,
        c.client_name,
        u.full_name,
        u.user_id AS staff_user_id,
        GROUP_CONCAT(s.service_name SEPARATOR ', ') AS service_names
    FROM daily_logs l
    JOIN clients c ON l.client_id = c.client_id
    JOIN daily_log_services dls ON l.log_id = dls.log_id
    JOIN services s ON dls.service_id = s.service_id
    JOIN users u ON l.staff_id = u.user_id
    GROUP BY l.log_id
    ORDER BY l.log_date DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Daily Work Logs</title>
    <style>
        body {
            background: #1a1a1a;
            color: #fff;
            font-family: 'Segoe UI';
        }
        .container {
            max-width: 95%;
            margin: 20px auto;
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

        h2.title {
            text-align: center;
            color: #00ffe1;
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

        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255,255,255,0.05);
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #444;
            text-align: center;
        }

        th {
            background-color: #111;
            color: #00ffe1;
        }

        tr:nth-child(even) {
            background-color: #2b2b2b;
        }

        .status {
            font-weight: bold;
        }

        .Pending { color: orange; }
        .Completed { color: #2ecc71; }

        .btn {
            padding: 6px 10px;
            border-radius: 4px;
            background: green;
            color: white;
            text-decoration: none;
            font-size: 13px;
        }

        /* PRINT */
        @media print {
            body { background: white; color: black; }
            .print-btn, .back-link, .btn, .action-col { display: none; }
            #print-header { display: block; }
            table { background: white; }
            th { background: #00ffe1 !important; color: black !important; }
        }
    </style>
</head>
<body>

<div id="print-header">
    <img src="IMG/logo.jpg">
    <h2>HIGHER LEVEL STUDIOS & DESIGN 🎥</h2>
</div>

<div class="container">
    <h2 class="title">📋 Daily Work Logs</h2>

    <div class="print-btn">
        <button onclick="window.print()">🖨️ Print</button>
    </div>

    <table>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Client</th>
            <th>Services</th>
            <th>Amount (KSh)</th>
            <th>Status</th>
            <th>Handled By</th>
            <th>Description</th>
            <th class="action-col">Action</th>
        </tr>

        <?php $i=1; while($log = $logs->fetch_assoc()): ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= date('d/m/Y', strtotime($log['log_date'])) ?></td>
            <td><?= htmlspecialchars($log['client_name']) ?></td>
            <td><?= htmlspecialchars($log['service_names']) ?></td>
            <td><?= number_format($log['price'],2) ?></td>
            <td class="status <?= $log['status'] ?>"><?= $log['status'] ?></td>
            <td><?= htmlspecialchars($log['full_name']) ?></td>
            <td><?= htmlspecialchars($log['description']) ?></td>
            <td class="action-col">
                <?php if ($log['status']=='Pending' && $log['staff_user_id']==$user_id): ?>
                    <a href="mark_log_done.php?id=<?= $log['log_id'] ?>" class="btn">Mark Completed</a>
                <?php elseif ($log['status']=='Completed'): ?>
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
