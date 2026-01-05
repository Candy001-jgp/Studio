<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$filter_source = $_GET['source'] ?? '';

$where_clauses = [];
if ($start_date) $where_clauses[] = "payment_date >= '$start_date'";
if ($end_date) $where_clauses[] = "payment_date <= '$end_date'";
if ($filter_source && in_array($filter_source, ['Booking', 'Daily Work'])) {
    $where_clauses[] = "source = '$filter_source'";
}
$where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$sql = "
    SELECT * FROM (
        SELECT c.client_name, b.amount_paid AS amount, b.booking_date AS payment_date, 'Booking' AS source
        FROM bookings b
        JOIN clients c ON b.client_id = c.client_id
        WHERE b.amount_paid > 0

        UNION ALL

        SELECT c.client_name, d.price AS amount, d.log_date AS payment_date, 'Daily Work' AS source
        FROM daily_logs d
        JOIN clients c ON d.client_id = c.client_id
        WHERE d.price > 0
    ) AS combined
    $where_sql
    ORDER BY payment_date DESC
";
$result = $conn->query($sql);

$booking_total = 0;
$daily_total = 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>💰 All Payments</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: url('IMG/INDEX.JPG') no-repeat center center fixed;
            background-size: cover;
            color: #003366;
            margin: 0;
            padding: 40px;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,80,0.2);
        }
        h2 {
            text-align: center;
            color: #0055cc;
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
        }
        #print-header h2 {
            color: black;
            margin: 0;
        }
        form.filter {
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        form.filter input, form.filter select {
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .print-btn {
            text-align: right;
            margin-top: 10px;
        }
        .print-btn button {
            padding: 8px 14px;
            background-color: #0055cc;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            background: #ffffff;
        }
        th, td {
            padding: 14px;
            border: 1px solid #cce0ff;
            text-align: left;
        }
        th {
            background: #0055cc;
            color: white;
        }
        tr:nth-child(even) {
            background: #f0f8ff;
        }
        .totals {
            margin-top: 30px;
            font-size: 18px;
            text-align: right;
            color: #0055cc;
        }
        .totals span {
            display: block;
            margin-bottom: 5px;
        }
        .back-link {
            margin-top: 20px;
            text-align: center;
        }
        .back-link a {
            color: #0055cc;
            text-decoration: underline;
        }

        /* PRINT STYLES */
        @media print {
            body {
                background: white;
                color: black;
                margin: 0;
            }
            #print-header {
                display: block;
            }
            form.filter,
            .print-btn,
            .back-link {
                display: none;
            }
            table {
                background: white;
                color: black;
            }
            th {
                background-color: #0055cc;
                color: white;
            }
            .page-number {
                display: block;
                position: fixed;
                bottom: 0;
                right: 0;
                padding: 10px;
                font-size: 12px;
                color: black;
            }
        }

        .page-number {
            display: none;
        }

        @page {
            margin: 20mm;
        }
    </style>
</head>
<body>
<div id="print-header">
    <img src="IMG/logo.jpg" alt="Logo">
    <h2>HIGHER LEVEL STUDIOS & DESIGN 🎥</h2>
</div>

<div class="container">
    <h2>💰 All Payments from Bookings & Daily Work</h2>

    <form method="GET" class="filter">
        <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
        <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
        <select name="source">
            <option value="">All Sources</option>
            <option value="Booking" <?= $filter_source === 'Booking' ? 'selected' : '' ?>>Booking</option>
            <option value="Daily Work" <?= $filter_source === 'Daily Work' ? 'selected' : '' ?>>Daily Work</option>
        </select>
        <button type="submit">Filter</button>
    </form>

    <div class="print-btn">
        <button onclick="window.print()">🖨️ Print</button>
    </div>

    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Client Name</th>
            <th>Amount Paid</th>
            <th>Payment Date</th>
            <th>Source</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0):
            $i = 1;
            while ($row = $result->fetch_assoc()):
                $amount = (float) $row['amount'];
                if ($row['source'] === 'Booking') {
                    $booking_total += $amount;
                } else {
                    $daily_total += $amount;
                }
                ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['client_name']) ?: 'N/A' ?></td>
                    <td>KSh <?= number_format($amount, 2) ?></td>
                    <td><?= htmlspecialchars($row['payment_date']) ?></td>
                    <td><?= $row['source'] ?></td>
                </tr>
            <?php endwhile; else: ?>
            <tr>
                <td colspan="5" style="text-align: center;">No payment records found.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="totals">
        <span>📘 Total from Bookings: <strong>KSh <?= number_format($booking_total, 2) ?></strong></span>
        <span>🛠️ Total from Daily Work: <strong>KSh <?= number_format($daily_total, 2) ?></strong></span>
        <span>💰 <u>Grand Total</u>: <strong>KSh <?= number_format($booking_total + $daily_total, 2) ?></strong></span>
    </div>

    <div class="back-link">
        <a href="dashboard.php">&larr; Back to Dashboard</a>
    </div>
</div>

<div class="page-number">Page</div>
</body>
</html>
