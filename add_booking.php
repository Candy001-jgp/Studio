<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$services = $conn->query("SELECT * FROM services ORDER BY service_name");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $client_name    = trim($_POST['client_name']);
    $client_contact = trim($_POST['client_contact']);
    $service_ids    = $_POST['service_ids'] ?? [];
    $booking_date   = $_POST['booking_date'];
    $booking_time   = $_POST['booking_time'];
    $notes          = $_POST['notes'];
    $amount_paid    = $_POST['amount_paid'];
    $assigned_staff = $_SESSION['user_id'];

    if (empty($service_ids)) {
        $error = "❌ Please select at least one service.";
    } else {

        /* CLIENT */
        $checkClient = $conn->prepare(
            "SELECT client_id FROM clients WHERE client_name=? AND client_contact=?"
        );
        $checkClient->bind_param("ss", $client_name, $client_contact);
        $checkClient->execute();
        $result = $checkClient->get_result();

        if ($result->num_rows > 0) {
            $client_id = $result->fetch_assoc()['client_id'];
        } else {
            $insertClient = $conn->prepare(
                "INSERT INTO clients (client_name, client_contact) VALUES (?, ?)"
            );
            $insertClient->bind_param("ss", $client_name, $client_contact);
            $insertClient->execute();
            $client_id = $insertClient->insert_id;
        }

        /* BOOKING */
        $stmt = $conn->prepare(
            "INSERT INTO bookings 
            (client_id, booking_date, booking_time, assigned_staff, notes, amount_paid, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
        );

        $stmt->bind_param(
            "issisd",
            $client_id,
            $booking_date,
            $booking_time,
            $assigned_staff,
            $notes,
            $amount_paid
        );

        if ($stmt->execute()) {

            $booking_id = $stmt->insert_id;

            /* SERVICES */
            $bs = $conn->prepare(
                "INSERT INTO booking_services (booking_id, service_id) VALUES (?, ?)"
            );

            foreach ($service_ids as $sid) {
                $bs->bind_param("ii", $booking_id, $sid);
                $bs->execute();
            }

            /* PAYMENT */
            $pay = $conn->prepare(
                "INSERT INTO payments 
                (client_id, booking_id, amount, payment_method, payment_date)
                VALUES (?, ?, ?, 'Cash', CURDATE())"
            );
            $pay->bind_param("iid", $client_id, $booking_id, $amount_paid);
            $pay->execute();

            $_SESSION['last_booking_id'] = $booking_id;
            $success = "✅ Booking created successfully!";
        } else {
            $error = "❌ Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Booking - Higher Level Studios</title>

<style>
body {
    background: url('IMG/DASHBOARD.JPG') no-repeat center center fixed;
    background-size: cover;
    font-family: 'Segoe UI', Tahoma;
    color: #fff;
}
.container {
    max-width: 600px;
    margin: 60px auto;
    background: rgba(0,0,0,0.6);
    padding: 30px;
    border-radius: 12px;
}
h2 { text-align:center; color:#00ffe1; }

input, textarea {
    width: 100%;
    padding: 10px;
    margin-top: 10px;
    border-radius: 6px;
    border: none;
}

/* DROPDOWN */
.dropdown {
    position: relative;
    margin-top: 10px;
}
.dropdown-btn {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    background: #fff;
    color: #000;
    cursor: pointer;
    text-align: left;
}
.dropdown-content {
    display: none;
    position: absolute;
    width: 100%;
    max-height: 180px;
    overflow-y: auto;
    background: #111;
    border-radius: 6px;
    padding: 10px;
    z-index: 100;
}
.dropdown-content label {
    display: block;
    margin-bottom: 6px;
    cursor: pointer;
}

button {
    background: linear-gradient(to right, #27ae60, #3498db);
    color: #fff;
    padding: 12px;
    width: 100%;
    border: none;
    border-radius: 6px;
    margin-top: 15px;
    cursor: pointer;
}

.success { background:#2ecc71; padding:10px; border-radius:6px; text-align:center; }
.error { background:#e74c3c; padding:10px; border-radius:6px; text-align:center; }

a { color:#fff; text-decoration:underline; }
a:hover { color:#00ffe1; }
</style>

<script>
function toggleDropdown() {
    document.getElementById("serviceDropdown").classList.toggle("show");
}

window.onclick = function(e) {
    if (!e.target.matches('.dropdown-btn')) {
        let dropdown = document.getElementById("serviceDropdown");
        if (dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
        }
    }
}
</script>

<style>
.show { display:block; }
</style>

</head>
<body>

<div class="container">
<h2>📅 Add New Booking</h2>

<?php if (isset($success)) echo "<div class='success'>$success</div>"; ?>
<?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>

<?php if (isset($_SESSION['last_booking_id'])): ?>
    <p style="text-align:center;">
        <a href="print_receipt.php?booking_id=<?= $_SESSION['last_booking_id'] ?>" target="_blank">
            🧾 Print Receipt
        </a>
    </p>
<?php endif; ?>

<form method="POST">

<label>Client Name</label>
<input type="text" name="client_name" required>

<label>Client Contact</label>
<input type="text" name="client_contact" required>

<label>Services</label>
<div class="dropdown">
    <div class="dropdown-btn" onclick="toggleDropdown()">Select Services ▾</div>
    <div class="dropdown-content" id="serviceDropdown">
        <?php while ($row = $services->fetch_assoc()): ?>
            <label>
                <input type="checkbox" name="service_ids[]" value="<?= $row['service_id'] ?>">
                <?= htmlspecialchars($row['service_name']) ?>
            </label>
        <?php endwhile; ?>
    </div>
</div>

<label>Amount Paid (KSh)</label>
<input type="number" step="0.01" name="amount_paid" required>

<label>Booking Date</label>
<input type="date" name="booking_date" required>

<label>Booking Time</label>
<input type="time" name="booking_time" required>

<p><strong>Logged in Staff:</strong> <?= htmlspecialchars($_SESSION['full_name']) ?></p>

<label>Notes</label>
<textarea name="notes" rows="3"></textarea>

<button type="submit">➕ Add Booking</button>

</form>

<p style="text-align:center;margin-top:20px;">
<a href="dashboard.php">← Back to Dashboard</a>
</p>

</div>
</body>
</html>
