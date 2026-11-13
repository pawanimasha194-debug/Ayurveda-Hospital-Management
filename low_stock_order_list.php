<?php
include 'config.php';
include 'hospital_info.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']);

$sql = "SELECT medicine_id, medicine_name, medicine_name_si, unit, unit_price, current_stock, reorder_level 
        FROM medicines 
        WHERE current_stock <= reorder_level 
        ORDER BY medicine_name ASC";
$result = $conn->query($sql);

$low_stock_items = [];
$grand_total = 0;

while ($row = $result->fetch_assoc()) {
    $target_stock = $row['reorder_level'] * 4;
    $suggested_qty = $target_stock - $row['current_stock'];
    if ($suggested_qty <= 0) $suggested_qty = $row['reorder_level'];

    $row['suggested_qty'] = $suggested_qty;
    $row['total_price'] = $suggested_qty * $row['unit_price'];
    $grand_total += $row['total_price'];
    
    $low_stock_items[] = $row;
}

if (isset($_POST['create_po'])) {
    $_SESSION['auto_order_items'] = $low_stock_items;
    header("Location: add_purchase_order.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Low Stock Order List - <?php echo htmlspecialchars($hospital_name_en); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&family=Noto+Sans+Sinhala:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="photo-medicines">

<div class="topbar">
    <h2>Low Stock Order List</h2>
    <a href="purchase_orders.php">&larr; Back to Purchase Orders</a>
</div>

<div class="print-actions">
    <a href="purchase_orders.php" class="btn" style="background:#607d8b;">&larr; Back</a>
    <div>
        <button onclick="window.print()" class="btn" style="background:#0d47a1;">🖨 Print List</button>
        <form method="POST" style="display:inline;">
            <button type="submit" name="create_po" class="btn" style="background:#1b5e20;">📝 Create PO from this List</button>
        </form>
    </div>
</div>

<div class="print-page">
    <?php renderLetterhead(); ?>

    <h2 class="doc-title">Low Stock Purchase Requirements</h2>
    <div class="doc-meta">Date: <?php echo date('d M Y'); ?></div>

    <?php if (count($low_stock_items) == 0): ?>
        <div class="empty-msg">
            <h3 style="color:#2e7d32;">✅ All Good!</h3>
            <p>No items are currently at or below the reorder level.</p>
        </div>
    <?php else: ?>
        <table class="print-table">
            <tr>
                <th>Code</th>
                <th>Medicine Name</th>
                <th>Stock</th>
                <th>Reorder</th>
                <th>Suggested Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
            <?php foreach ($low_stock_items as $item): ?>
            <tr>
                <td><b>MED-<?php echo str_pad($item['medicine_id'], 3, '0', STR_PAD_LEFT); ?></b></td>
                <td>
                    <b><?php echo htmlspecialchars($item['medicine_name']); ?></b>
                    <?php if(!empty($item['medicine_name_si'])): ?>
                        <br><small style="font-family: 'Noto Sans Sinhala', sans-serif; color:#666;"><?php echo htmlspecialchars($item['medicine_name_si']); ?></small>
                    <?php endif; ?>
                </td>
                <td style="color:#c62828; font-weight:bold;"><?php echo $item['current_stock']; ?></td>
                <td><?php echo $item['reorder_level']; ?></td>
                <td style="font-weight:bold; color:#1b5e20; background:#e8f5e9;">
                    <?php echo $item['suggested_qty']; ?> <?php echo htmlspecialchars($item['unit']); ?>
                </td>
                <td><?php echo number_format($item['unit_price'], 2); ?></td>
                <td><?php echo number_format($item['total_price'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="6" style="text-align: right; font-weight: bold; font-size: 16px;">Estimated Grand Total:</td>
                <td style="font-weight: bold; font-size: 16px; color: #1b5e20;">LKR <?php echo number_format($grand_total, 2); ?></td>
            </tr>
        </table>
    <?php endif; ?>

    <div class="signature-row">
        <div class="signature-box">Prepared By (Pharmacist)</div>
        <div class="signature-box">Approved By (Admin)</div>
    </div>
</div>

</body>
</html>