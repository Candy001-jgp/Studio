<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';

$type = null;
$data = null;

if (isset($_GET['booking_id'])) {
    $type = 'booking';
    $stmt = $conn->prepare("
        SELECT 
            b.booking_id,
            b.booking_date,
            b.booking_time,
            b.amount_paid,
            b.status,
            c.client_name,
            c.client_contact,
            u.full_name AS staff_name,
            GROUP_CONCAT(s.service_name SEPARATOR ', ') AS service_name
        FROM bookings b
        JOIN clients c ON b.client_id = c.client_id
        JOIN booking_services bs ON b.booking_id = bs.booking_id
        JOIN services s ON bs.service_id = s.service_id
        JOIN users u ON b.assigned_staff = u.user_id
        WHERE b.booking_id = ?
        GROUP BY b.booking_id
    ");
    $stmt->bind_param("i", $_GET['booking_id']);

} elseif (isset($_GET['log_id'])) {
    $type = 'log';
    $stmt = $conn->prepare("
        SELECT 
            l.log_id,
            l.log_date,
            l.price,
            l.status,
            c.client_name,
            c.client_contact,
            u.full_name AS staff_name,
            GROUP_CONCAT(s.service_name SEPARATOR ', ') AS service_name
        FROM daily_logs l
        JOIN clients c ON l.client_id = c.client_id
        JOIN daily_log_services dls ON l.log_id = dls.log_id
        JOIN services s ON dls.service_id = s.service_id
        JOIN users u ON l.staff_id = u.user_id
        WHERE l.log_id = ?
        GROUP BY l.log_id
    ");
    $stmt->bind_param("i", $_GET['log_id']);

} else {
    echo "❌ ID missing.";
    exit;
}

$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows == 0) {
    echo "❌ Record not found.";
    exit;
}
$data = $res->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Receipt – Higher Level Studios</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f0f0f0; padding: 20px; }
    .receipt { background: white; margin: auto; padding: 30px; max-width: 680px; border-radius: 8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
    .header { text-align: center; border-bottom: 2px solid #09a; padding-bottom: 15px; }
    .header img { width: 80px; margin-bottom: 10px; }
    .header h1 { margin: 5px 0; color: #098; }
    .section { margin: 20px 0; }
    .section h2 { font-size: 18px; color: #09a; margin-bottom: 10px; }
    .section p { margin: 6px 0; font-size: 16px; }
    .highlight { font-weight: bold; }
    .footer { text-align: center; padding-top: 20px; border-top: 1px solid #ddd; color: #555; }
    .footer p { margin: 4px; }
    .btn-wrap { text-align: center; margin-top: 25px; }
    .btn { background: #098; color: white; padding: 12px 25px; border: none; border-radius:6px; text-decoration: none; font-size: 15px; margin: 0 8px; display: inline-block; }
    .btn:hover { background: #007; }
    @media print { .btn { display: none; } }
  </style>
</head>
<body>
  <div class="receipt">
    <div class="header">
      <img src="IMG/logo.jpg" alt="Logo">
      <h1>HIGHER LEVEL STUDIOS & DESIGN 🎥</h1>
      <em>Official Receipt</em>
    </div>

    <div class="section">
      <h2>Client Information</h2>
      <p><span class="highlight">Name:</span> <?=htmlspecialchars($data['client_name'])?></p>
      <p><span class="highlight">Contact:</span> <?=htmlspecialchars($data['client_contact'])?></p>
    </div>

    <div class="section">
      <h2><?= $type === 'booking' ? 'Booking Details' : 'Work Log Details' ?></h2>
      <p><span class="highlight">Service:</span> <?=htmlspecialchars($data['service_name'])?></p>
      <p><span class="highlight">Amount:</span> KSh <?=number_format($data['price'] ?? $data['amount_paid'],2)?></p>
      <p><span class="highlight"><?= $type==='booking' ? 'Date & Time:' : 'Date:' ?></span> 
         <?=htmlspecialchars($data['booking_date'] ?? $data['log_date'])?> 
         <?= $type==='booking' ? $data['booking_time'] : '' ?></p>
      <p><span class="highlight">Handled By:</span> <?=htmlspecialchars($data['staff_name'])?></p>
    </div>

    <div class="footer">
      <p><strong>CEO:</strong> Oriaso Mike Stanley | 📞 0114561427</p>
      <p><strong>CFO:</strong> Mukami Christine Kariuki | 📞 0791191635</p>
      <p>Thank you for choosing Higher Level Studios.</p>
    </div>
  </div>

  <div class="btn-wrap">
    <button class="btn" onclick="window.print()">🖨️ Print</button>
    <a href="dashboard.php" class="btn">⬅️ Back</a>
  </div>
</body>
</html>
