<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['expense_date'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $created_by = $_SESSION['user_id'];
    $edit_id = $_POST['edit_id'] ?? '';

    if (!empty($edit_id)) {
        // Delete old entry first
        $stmt = $conn->prepare("DELETE FROM expenses WHERE id=?");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $stmt->close();

        // Insert updated entry
        $stmt = $conn->prepare("INSERT INTO expenses (expense_date, category, description, amount, created_by) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdi", $date, $category, $description, $amount, $created_by);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert new entry
        $stmt = $conn->prepare("INSERT INTO expenses (expense_date, category, description, amount, created_by) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdi", $date, $category, $description, $amount, $created_by);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: expenses.php");
    exit();
}

// Delete action
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM expenses WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    header("Location: expenses.php");
    exit();
}

// Edit action
$edit_expense = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result_edit = $conn->query("SELECT * FROM expenses WHERE id=$edit_id");
    $edit_expense = $result_edit->fetch_assoc();
}

// Fetch all expenses
$result = $conn->query("SELECT * FROM expenses ORDER BY expense_date DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Expenses - Higher Level Studios</title>
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
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            max-width: 1000px;
            margin: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-dashboard {
            display: inline-block;
            padding: 10px 20px;
            background: #34495e;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            box-shadow: 0 3px 8px rgba(0,0,0,0.2);
            transition: background 0.3s ease, transform 0.2s ease;
            margin-bottom: 20px;
        }

        .btn-dashboard:hover {
            background: #2c3e50;
            transform: translateY(-2px);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #2c3e50;
        }

        form {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        form label {
            font-weight: bold;
            color: #2c3e50;
            display: block;
            margin-top: 10px;
        }

        form select,
        form input[type="date"],
        form input[type="number"],
        form textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        form button {
            margin-top: 15px;
            padding: 12px 20px;
            background-color: #27ae60;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        form button:hover {
            background-color: #219150;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 12px;
            border: 1px solid #eee;
            text-align: left;
            font-size: 14px;
        }

        th {
            background-color: #2980b9;
            color: white;
        }

        .btn-edit {
            color: #2980b9;
            font-weight: bold;
            text-decoration: none;
            margin-right: 10px;
        }

        .btn-edit:hover {
            text-decoration: underline;
        }

        .btn-delete {
            color: #e74c3c;
            font-weight: bold;
            text-decoration: none;
        }

        .btn-delete:hover {
            text-decoration: underline;
        }

        .total-row {
            font-weight: bold;
            background: #f4f4f4;
        }

        @media print {
            form, .btn-dashboard, .btn-edit, .btn-delete {
                display: none;
            }

            .container {
                box-shadow: none;
                background: white;
            }
        }
    </style>
</head>
<body>
<div class="container">

    <a href="dashboard.php" class="btn-dashboard">⬅ Back to Dashboard</a>
    <h2><?php echo $edit_expense ? "Edit Expense" : "Add Expense"; ?></h2>

    <form method="POST">
        <input type="hidden" name="edit_id" value="<?php echo $edit_expense['id'] ?? ''; ?>">

        <label>Date:</label>
        <input type="date" name="expense_date" value="<?php echo $edit_expense['expense_date'] ?? ''; ?>" required>
        
        <label>Category:</label>
        <select name="category" required>
            <?php
            $categories = ["Rent","Utilities","Salaries","Equipment","Miscellaneous"];
            foreach ($categories as $cat) {
                $selected = ($edit_expense && $edit_expense['category'] == $cat) ? "selected" : "";
                echo "<option $selected>$cat</option>";
            }
            ?>
        </select>

        <label>Description:</label>
        <textarea name="description"><?php echo $edit_expense['description'] ?? ''; ?></textarea>

        <label>Amount (KSh):</label>
        <input type="number" step="0.01" name="amount" value="<?php echo $edit_expense['amount'] ?? ''; ?>" required>

        <button type="submit"><?php echo $edit_expense ? "Update Expense" : "Add Expense"; ?></button>
    </form>

    <h3>All Expenses</h3>
    <table>
        <tr>
            <th>Date</th>
            <th>Category</th>
            <th>Description</th>
            <th>Amount (KSh)</th>
            <th>Actions</th>
        </tr>
        <?php
        $total = 0;
        while($row = $result->fetch_assoc()):
            $total += $row['amount'];
        ?>
        <tr>
            <td><?php echo $row['expense_date']; ?></td>
            <td><?php echo $row['category']; ?></td>
            <td><?php echo $row['description']; ?></td>
            <td><?php echo number_format($row['amount'], 2); ?></td>
            <td>
                <a href="?edit=<?php echo $row['id']; ?>" class="btn-edit">✏ Edit</a>
                <a href="?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this expense?');">🗑 Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
        <tr class="total-row">
            <th colspan="3">Total</th>
            <th colspan="2">KSh <?php echo number_format($total, 2); ?></th>
        </tr>
    </table>
</div>
</body>
</html>
