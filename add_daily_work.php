<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

$clients  = $conn->query("SELECT * FROM clients ORDER BY client_name");
$services = $conn->query("SELECT * FROM services ORDER BY service_name");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $existing_client_id = $_POST['client_id'];
    $manual_name        = $_POST['manual_name'] ?? null;
    $manual_contact     = $_POST['manual_contact'] ?? null;
    $service_ids        = $_POST['service_ids'] ?? [];
    $price              = $_POST['price'];
    $description        = $_POST['description'];
    $status             = $_POST['status'];
    $log_date           = date('Y-m-d');

    if (empty($service_ids)) {
        $error = "❌ Please select at least one service.";
    } else {

        /* CLIENT */
        if (empty($existing_client_id) && $manual_name && $manual_contact) {
            $insertClient = $conn->prepare(
                "INSERT INTO clients (client_name, client_contact) VALUES (?, ?)"
            );
            $insertClient->bind_param("ss", $manual_name, $manual_contact);
            $insertClient->execute();
            $client_id = $insertClient->insert_id;
        } else {
            $client_id = $existing_client_id;
        }

        /* DAILY LOG */
        $stmt = $conn->prepare(
            "INSERT INTO daily_logs 
            (client_id, price, description, status, staff_id, log_date)
            VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "idssis",
            $client_id,
            $price,
            $description,
            $status,
            $user_id,
            $log_date
        );

        if ($stmt->execute()) {

            $log_id = $stmt->insert_id;

            /* SERVICES */
            $ls = $conn->prepare(
                "INSERT INTO daily_log_services (log_id, service_id) VALUES (?, ?)"
            );

            foreach ($service_ids as $sid) {
                $ls->bind_param("ii", $log_id, $sid);
                $ls->execute();
            }

            $success = "✔️ Daily work log submitted successfully!";
        } else {
            $error = "❌ Error: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Daily Work Log</title>

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
    background: #fff;
    color: #000;
    padding: 10px;
    border-radius: 6px;
    cursor: pointer;
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
}

.show { display:block; }

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
</style>

<script>
function toggleServices() {
    document.getElementById("servicesBox").classList.toggle("show");
}
</script>

</head>
<body>

<div class="container">
<h2>🛠️ Daily Work Log</h2>

<?php if (isset($success)) echo "<div class='success'>$success</div>"; ?>
<?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>

<form method="POST">

<label>Date</label>
<input type="text" value="<?= date('d/m/Y') ?>" readonly>

<label>Client</label>
<select name="client_id">
    <option value="">-- Select Existing Client --</option>
    <?php while ($row = $clients->fetch_assoc()): ?>
        <option value="<?= $row['client_id'] ?>">
            <?= htmlspecialchars($row['client_name']) ?>
        </option>
    <?php endwhile; ?>
</select>

<p>OR enter new client details</p>
<input type="text" name="manual_name" placeholder="Client Name">
<input type="text" name="manual_contact" placeholder="Client Contact">

<label>Services</label>
<div class="dropdown">
    <div class="dropdown-btn" onclick="toggleServices()">Select Services ▾</div>
    <div class="dropdown-content" id="servicesBox">
        <?php
        $services->data_seek(0);
        while ($row = $services->fetch_assoc()):
        ?>
        <label>
            <input type="checkbox" name="service_ids[]" value="<?= $row['service_id'] ?>">
            <?= htmlspecialchars($row['service_name']) ?>
        </label>
        <?php endwhile; ?>
    </div>
</div>

<label>Price (KSh)</label>
<input type="number" step="0.01" name="price" required>

<label>Description</label>
<textarea name="description" required></textarea>

<label>Status</label>
<select name="status" required>
    <option value="Completed">Completed</option>
    <option value="Pending">Pending</option>
</select>

<button type="submit">✔️ Submit Log</button>

</form>

<p style="text-align:center;margin-top:20px;">
<a href="dashboard.php">← Back to Dashboard</a>
</p>

</div>
</body>
</html>
