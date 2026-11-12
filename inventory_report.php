<?php
include 'config.php';
include 'hospital_info.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']); // Pharmacist and admin only

// Optional period filter (defaults to last 30 days -> today)
$period_from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-d', strtotime('-30 days'));
$period_to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');

// Medicine list with assigned supplier (from most recent batch) and stock status
$sql = "SELECT m.*,
               (SELECT s2.supplier_name FROM medicine_batches b2
                JOIN suppliers s2 ON s2.supplier_id = b2.supplier_id
                WHERE b2.medicine_id = m.medicine_id
                ORDER BY b2.received_date DESC LIMIT 1) AS assigned_supplier
        FROM medicines m
        ORDER BY m.medicine_name ASC";
$result = $conn->query($sql);

$medicines = [];
$totalValue = 0;
$adequateCount = 0;
$lowCount = 0;
while ($row = $result->fetch_assoc()) {
    $medicines[] = $row;
    $totalValue += $row['current_stock'] * $row['unit_price'];
    if ($row['current_stock'] <= $row['reorder_level']) {
        $lowCount++;
    } else {
        $adequateCount++;
    }
}
$totalCount = count($medicines);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Inventory Status Report - <?php echo htmlspecialchars($hospital_name_en); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="photo-medicines">

<div class="print-actions">
    <a href="reports.php" class="btn">&larr; Back</a>
    <button onclick="window.print()" class="btn">🖨 Print</button>
</div>

<div class="print-page">
    <?php renderLetterhead(); ?>

    <h2 class="doc-title">MEDICINE INVENTORY STATUS REPORT</h2>
    <div class="doc-meta">
        Period: <?php echo date('d/m/Y', strtotime($period_from)); ?> - <?php echo date('d/m/Y', strtotime($period_to)); ?>
        &nbsp;|&nbsp; Generated: <?php echo date('j/n/Y, g:i A'); ?>
    </div>

    <div class="print-summary-row">
        <div><b>Total Medicines:</b> <?php echo $totalCount; ?></div>
        <div><b>Total Inventory Value:</b> LKR <?php echo number_format($totalValue, 2); ?></div>
        <div><b>Adequately Stocked:</b> <?php echo $adequateCount; ?> Items</div>
        <div><b>Low Stock Warning:</b> <?php echo $lowCount; ?> Items (Need Purchase Order)</div>
    </div>

    <h3 style="margin-bottom:8px;">MEDICINE INVENTORY DETAILS</h3>
    <table class="print-table">
        <tr>
            <th>#</th>
            <th>Medicine</th>
            <th>Category</th>
            <th>Assigned Supplier</th>
            <th>Stock Level</th>
            <th>Reorder Level</th>
            <th>Unit Price</th>
            <th>Stock Status</th>
        </tr>
        <?php $i = 1; foreach ($medicines as $row):
            $isLow = $row['current_stock'] <= $row['reorder_level'];
        ?>
        <tr>
            <td><?php echo $i++; ?></td>
            <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
            <td><?php echo htmlspecialchars($row['medicine_type']); ?></td>
            <td><?php echo htmlspecialchars($row['assigned_supplier'] ?: '-'); ?></td>
            <td><?php echo $row['current_stock']; ?></td>
            <td><?php echo $row['reorder_level']; ?></td>
            <td><?php echo number_format($row['unit_price'], 2); ?></td>
            <td><span class="stock-status <?php echo $isLow ? 'stock-low' : 'stock-ok'; ?>"><?php echo $isLow ? 'Low Stock' : 'Adequate'; ?></span></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="signature-row">
        <div class="signature-box">Prepared By</div>
        <div class="signature-box">Approved By</div>
    </div>
</div>

</body>
</html>
