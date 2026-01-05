<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Filters
$filter_start = $_GET['start_date'] ?? '';
$filter_end = $_GET['end_date'] ?? '';
$filter_service = $_GET['service'] ?? '';
$filter_status = $_GET['status'] ?? '';

$where_clauses = [];

if (!empty($filter_start) && !empty($filter_end)) {
    $start_date_sql = date('Y-m-d', strtotime($filter_start));
    $end_date_sql = date('Y-m-d', strtotime($filter_end));
    $where_clauses[] = "date BETWEEN '$start_date_sql' AND '$end_date_sql'";
}

if (!empty($filter_service)) {
    $where_clauses[] = "service_name LIKE '%" . $conn->real_escape_string($filter_service) . "%'";
}

if (!empty($filter_status)) {
    $where_clauses[] = "status = '" . $conn->real_escape_string($filter_status) . "'";
}

$filter_sql = "";
if (!empty($where_clauses)) {
    $filter_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Main combined query
$sql = "
    SELECT * FROM (
        -- Bookings (multi-service)
        SELECT 
            'Booking' AS type,
            b.booking_date AS date,
            c.client_name,
            GROUP_CONCAT(s.service_name SEPARATOR ', ') AS service_name,
            b.amount_paid AS income,
            0 AS expense,
            b.status,
            u.full_name AS staff_name
        FROM bookings b
        JOIN clients c ON b.client_id = c.client_id
        JOIN booking_services bs ON b.booking_id = bs.booking_id
        JOIN services s ON bs.service_id = s.service_id
        JOIN users u ON b.assigned_staff = u.user_id
        GROUP BY b.booking_id

        UNION ALL

        -- Daily Logs (multi-service)
        SELECT 
            'Daily Work' AS type, 
            d.log_date AS date, 
            c.client_name, 
            GROUP_CONCAT(s.service_name SEPARATOR ', ') AS service_name, 
            d.price AS income, 
            0 AS expense, 
            d.status, 
            u.full_name AS staff_name
        FROM daily_logs d
        JOIN clients c ON d.client_id = c.client_id
        JOIN daily_log_services dls ON d.log_id = dls.log_id
        JOIN services s ON dls.service_id = s.service_id
        JOIN users u ON d.staff_id = u.user_id
        GROUP BY d.log_id

        UNION ALL

        -- Expenses
        SELECT 
            'Expense' AS type, 
            e.expense_date AS date, 
            '' AS client_name, 
            e.category AS service_name, 
            0 AS income, 
            e.amount AS expense, 
            'Paid' AS status, 
            '' AS staff_name
        FROM expenses e
    ) AS combined
    $filter_sql
    ORDER BY date DESC
";

$result = $conn->query($sql);
$total_income = 0;
$total_expenses = 0;

// Fetch all services for dropdown filter
$service_query = $conn->query("
    SELECT DISTINCT service_name FROM (
        SELECT service_name FROM services
        UNION
        SELECT category AS service_name FROM expenses
    ) AS all_services
");
$statuses = ['Pending','Completed','Cancelled','Paid'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Activity Report</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: url('IMG/DASHBOARD.JPG') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 40px;
            color: #333;
        }

        .container {
            background: rgba(255,255,255,0.95);
            padding: 30px;
            border-radius: 15px;
            max-width: 1200px;
            margin: auto;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .print-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .print-header img.logo {
            width: 90px;
            height: 90px;
            object-fit: contain;
            border-radius: 50%;
            box-shadow: 0 0 8px rgba(0,0,0,0.3);
        }

        .print-header h1 {
            margin: 10px 0 5px;
            font-size: 28px;
            color: #2c3e50;
        }

        .print-header p {
            margin: 0;
            font-size: 14px;
            color: #555;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }

        .top-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .top-actions .back-btn {
            padding: 8px 15px;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        .top-actions .back-btn:hover {
            background-color: #c0392b;
        }

        form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        form label {
            font-weight: bold;
            color: #2c3e50;
        }

        form select,
        form input[type="date"],
        form button {
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        form button {
            background-color: #27ae60;
            color: white;
            border: none;
            cursor: pointer;
        }

        form button:hover {
            background-color: #219150;
        }

        .print-btn {
            background-color: #2980b9;
            margin-left: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
            font-size: 14px;
        }

        th {
            background-color: #2980b9;
            color: white;
        }

        .total {
            font-weight: bold;
            font-size: 18px;
            margin-top: 25px;
            text-align: right;
        }

        @media print {
            form, .print-btn, .back-btn {
                display: none;
            }

            .container {
                box-shadow: none;
                background: white;
            }

            .print-header {
                display: block;
            }
        }
    </style>
</head>
<body>
<div class="container">

    <div class="print-header">
        <img src="IMG/logo.jpg" alt="Logo" class="logo">
        <h1>HIGHER LEVEL STUDIOS & DESIGN 🎥</h1>
        <p><em>Full Activity Report</em></p>
        <hr>
    </div>

    <div class="top-actions">
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>

    <form method="get">
        <label>From:
            <input type="date" name="start_date" value="<?= htmlspecialchars($filter_start) ?>">
        </label>

        <label>To:
            <input type="date" name="end_date" value="<?= htmlspecialchars($filter_end) ?>">
        </label>

        <label>Service/Category:
            <select name="service">
                <option value="">All</option>
                <?php while ($s = $service_query->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($s['service_name']) ?>" <?= ($filter_service == $s['service_name']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['service_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </label>

        <label>Status:
            <select name="status">
                <option value="">All</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= $status ?>" <?= ($filter_status == $status) ? 'selected' : '' ?>><?= $status ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <button type="submit">Filter</button>
        <button type="button" class="print-btn" onclick="window.print()">🖨️ Print</button>
    </form>

    <table>
        <thead>
        <tr>
            <th>Type</th>
            <th>Date</th>
            <th>Client</th>
            <th>Service/Category</th>
            <th>Income (KSh)</th>
            <th>Expense (KSh)</th>
            <th>Status</th>
            <th>Staff</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()):
            $total_income += $row['income'];
            $total_expenses += $row['expense'];
        ?>
            <tr>
                <td><?= htmlspecialchars($row['type']) ?></td>
                <td><?= htmlspecialchars($row['date']) ?></td>
                <td><?= htmlspecialchars($row['client_name']) ?></td>
                <td><?= htmlspecialchars($row['service_name']) ?></td>
                <td><?= $row['income'] > 0 ? number_format($row['income'],2) : '-' ?></td>
                <td><?= $row['expense'] > 0 ? number_format($row['expense'],2) : '-' ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= htmlspecialchars($row['staff_name']) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <div class="total">
        Total Income: <strong>KSh <?= number_format($total_income,2) ?></strong><br>
        Total Expenses: <strong>KSh <?= number_format($total_expenses,2) ?></strong><br>
        Net Income: <strong>KSh <?= number_format($total_income - $total_expenses,2) ?></strong>
    </div>
</div>
</body>
</html>
