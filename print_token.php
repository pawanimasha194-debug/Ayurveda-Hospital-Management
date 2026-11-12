<?php
include 'config.php';
include 'hospital_info.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['reception']);

if (!isset($_GET['id'])) { header("Location: tokens.php"); exit(); }
$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM tokens WHERE token_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$token = $stmt->get_result()->fetch_assoc();
if (!$token) { header("Location: tokens.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Token #<?php echo $token['token_number']; ?> - <?php echo htmlspecialchars($hospital_name_en); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="reception-style.css">
</head>
<body class="reception-body">

<div class="print-actions">
    <a href="reception_dashboard.php" class="btn">&larr; Back</a>
    <button onclick="window.print()" class="btn">🖨 Print</button>
</div>

<div class="print-page token-slip">
    <?php renderLetterhead(); ?>

    <h2 class="doc-title">PATIENT TOKEN SLIP</h2>

    <div class="token-slip-number">
        <div class="token-slip-label">TOKEN NUMBER</div>
        <div class="token-slip-big">#<?php echo str_pad($token['token_number'], 3, '0', STR_PAD_LEFT); ?></div>
    </div>

    <table class="print-table">
        <tr><td><b>Patient Name</b></td><td><?php echo htmlspecialchars($token['patient_name']); ?></td></tr>
        <tr><td><b>Phone</b></td><td><?php echo htmlspecialchars($token['phone_number'] ?: '-'); ?></td></tr>
        <tr><td><b>Complaint</b></td><td><?php echo htmlspecialchars($token['illness_details'] ?: '-'); ?></td></tr>
        <tr><td><b>Date</b></td><td><?php echo date('d M Y, g:i A', strtotime($token['created_at'])); ?></td></tr>
        <tr><td><b>Status</b></td><td><?php echo $token['status']; ?></td></tr>
    </table>

    <p class="slip-note">Please keep this slip. You will be called by your token number.</p>

    <div class="signature-row">
        <div class="signature-box">Receptionist</div>
        <div class="signature-box">Patient Signature</div>
    </div>
</div>

</body>
</html>