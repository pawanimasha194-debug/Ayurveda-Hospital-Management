<?php
include 'config.php';
include 'hospital_info.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']); // Pharmacist and admin only

if (!isset($_GET['id'])) {
    header("Location: receive_medicine.php");
    exit();
}
$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT r.receiving_id, r.quantity_received, r.received_date,
                                m.medicine_name, m.unit,
                                b.batch_no, b.expiry_date,
                                s.supplier_name, s.contact_person, s.contact_number, s.address,
                                u.full_name AS received_by_name
                         FROM medicine_receiving r
                         JOIN medicines m ON r.medicine_id = m.medicine_id
                         JOIN suppliers s ON r.supplier_id = s.supplier_id
                         LEFT JOIN medicine_batches b ON r.batch_id = b.batch_id
                         LEFT JOIN users u ON r.received_by = u.user_id
                         WHERE r.receiving_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$note = $stmt->get_result()->fetch_assoc();

if (!$note) {
    header("Location: receive_medicine.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Delivery Note #<?php echo $id; ?> - <?php echo htmlspecialchars($hospital_name_en); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="photo-medicines">

<div class="print-actions">
    <a href="receive_medicine.php" class="btn">&larr; Back</a>
    <button onclick="window.print()" class="btn">🖨 Print</button>
</div>

<div class="print-page">
    <?php renderLetterhead(); ?>

    <h2 class="doc-title">GOODS RECEIVED / DELIVERY NOTE</h2>
    <div class="doc-meta">Receiving No: GRN-<?php echo str_pad($note['receiving_id'], 5, '0', STR_PAD_LEFT); ?> &nbsp;|&nbsp; Date: <?php echo $note['received_date']; ?></div>

    <div class="print-summary-row">
        <div><b>Supplier:</b> <?php echo htmlspecialchars($note['supplier_name']); ?></div>
        <div><b>Contact Person:</b> <?php echo htmlspecialchars($note['contact_person'] ?: '-'); ?></div>
        <div><b>Contact Number:</b> <?php echo htmlspecialchars($note['contact_number'] ?: '-'); ?></div>
        <div><b>Address:</b> <?php echo htmlspecialchars($note['address'] ?: '-'); ?></div>
    </div>

    <table class="print-table">
        <tr>
            <th>Medicine</th>
            <th>Batch No</th>
            <th>Quantity Received</th>
            <th>Unit</th>
            <th>Expiry Date</th>
        </tr>
        <tr>
            <td><?php echo htmlspecialchars($note['medicine_name']); ?></td>
            <td><?php echo htmlspecialchars($note['batch_no'] ?: '-'); ?></td>
            <td><?php echo $note['quantity_received']; ?></td>
            <td><?php echo htmlspecialchars($note['unit']); ?></td>
            <td><?php echo $note['expiry_date'] ?: '-'; ?></td>
        </tr>
    </table>

    <p style="font-size:13px; color:var(--ink-soft);">Received by: <?php echo htmlspecialchars($note['received_by_name'] ?: '-'); ?></p>

    <div class="signature-row">
        <div class="signature-box">Received By (Hospital Staff)</div>
        <div class="signature-box">Supplier / Delivery Person</div>
    </div>
</div>

</body>
</html>
