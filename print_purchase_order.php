<?php
include 'config.php';
include 'hospital_info.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']);

if (!isset($_GET['id'])) { header("Location: purchase_orders.php"); exit(); }
$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT po.*, s.supplier_name, s.contact_person, s.contact_number, s.email, s.address, u.full_name AS created_by_name 
                        FROM purchase_orders po 
                        JOIN suppliers s ON po.supplier_id = s.supplier_id 
                        LEFT JOIN users u ON po.created_by = u.user_id 
                        WHERE po.po_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$po = $stmt->get_result()->fetch_assoc();

if (!$po) { header("Location: purchase_orders.php"); exit(); }

$stmt2 = $conn->prepare("SELECT poi.*, m.medicine_name, m.medicine_name_si, m.unit 
                         FROM purchase_order_items poi 
                         JOIN medicines m ON poi.medicine_id = m.medicine_id 
                         WHERE poi.po_id = ?");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$items = $stmt2->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Purchase Order #PO-<?php echo str_pad($po['po_id'], 4, '0', STR_PAD_LEFT); ?> - <?php echo htmlspecialchars($hospital_name_en); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.print-page { max-width: 850px; margin: 30px auto; background: #fff; padding: 40px 50px; border: 1px solid #dfece0; border-radius: 10px; box-shadow: 0 8px 30px rgba(27,94,32,0.08); }
.doc-title { text-align: center; font-family: 'Zilla Slab', serif; font-size: 22px; color: #1b5e20; margin: 10px 0; text-transform: uppercase; }
.po-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; background: #f8fbf7; padding: 16px 20px; border-radius: 8px; border: 1px solid #dfece0; }
.po-meta .box { display: flex; flex-direction: column; gap: 4px; font-size: 14px; }
.po-meta .box b { color: #1b5e20; }
.print-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.print-table th { background: #1b5e20; color: #fff; padding: 10px; text-align: left; font-size: 13px; }
.print-table td { padding: 10px; border-bottom: 1px solid #dfece0; font-size: 14px; }
.print-table tr:nth-child(even) { background: #f8fbf7; }
.signature-row { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-top: 60px; }
.signature-box { border-top: 1px solid #263238; padding-top: 8px; text-align: center; font-size: 13px; color: #5f6f68; }
@media print {
    .topbar, .print-actions { display: none !important; }
    .print-page { border: none; box-shadow: none; margin: 0; padding: 0; }
}
</style>
</head>
<body class="photo-medicines">

<div class="topbar">
    <h2>Purchase Order Preview</h2>
    <a href="purchase_orders.php">&larr; Back to Orders</a>
</div>

<div class="print-actions" style="max-width: 850px; margin: 0 auto 10px; text-align: right;">
    <button onclick="window.print()" class="btn" style="background:#1b5e20;">🖨 Print Order</button>
</div>

<div class="print-page">
    <?php renderLetterhead(); ?>

    <h2 class="doc-title">Purchase Order</h2>
    <div style="text-align: center; font-size: 14px; margin-bottom: 20px; color: #5f6f68;">
        <b>PO No:</b> #PO-<?php echo str_pad($po['po_id'], 4, '0', STR_PAD_LEFT); ?> &nbsp;|&nbsp; 
        <b>Date:</b> <?php echo date('d M Y', strtotime($po['order_date'])); ?>
    </div>

    <div class="po-meta">
        <div class="box">
            <b>TO (Supplier):</b>
            <span><?php echo htmlspecialchars($po['supplier_name']); ?></span>
            <span><?php echo htmlspecialchars($po['contact_person']); ?> (<?php echo htmlspecialchars($po['contact_number']); ?>)</span>
            <span><?php echo htmlspecialchars($po['address']); ?></span>
        </div>
        <div class="box" style="text-align: right;">
            <b>Expected Delivery:</b>
            <span><?php echo $po['expected_date'] ? date('d M Y', strtotime($po['expected_date'])) : 'ASAP'; ?></span>
            <b style="margin-top:10px;">Prepared By:</b>
            <span><?php echo htmlspecialchars($po['created_by_name']); ?></span>
        </div>
    </div>

    <table class="print-table">
        <tr>
            <th>#</th>
            <th>Medicine Name</th>
            <th>Quantity</th>
            <th>Unit Price (LKR)</th>
            <th>Total (LKR)</th>
        </tr>
        <?php 
        $i = 1; 
        $grand_total = 0;
        while ($m = $items->fetch_assoc()): 
            $total = $m['quantity'] * $m['unit_price'];
            $grand_total += $total;
        ?>
        <tr>
            <td><?php echo $i++; ?></td>
            <td>
                <b><?php echo htmlspecialchars($m['medicine_name']); ?></b><br>
                <small style="color:#666;"><?php echo htmlspecialchars($m['medicine_name_si']); ?></small>
            </td>
            <td><?php echo $m['quantity']; ?> <?php echo htmlspecialchars($m['unit']); ?></td>
            <td><?php echo number_format($m['unit_price'], 2); ?></td>
            <td><?php echo number_format($total, 2); ?></td>
        </tr>
        <?php endwhile; ?>
        <tr>
            <td colspan="4" style="text-align: right; font-weight: bold; font-size: 16px;">Grand Total:</td>
            <td style="font-weight: bold; font-size: 16px; color: #1b5e20;">LKR <?php echo number_format($grand_total, 2); ?></td>
        </tr>
    </table>

    <?php if (!empty($po['notes'])): ?>
        <div style="background: #fff8e1; border-left: 4px solid #c9a227; padding: 12px; font-size: 14px; margin-top: 20px;">
            <b>Notes:</b> <?php echo nl2br(htmlspecialchars($po['notes'])); ?>
        </div>
    <?php endif; ?>

    <div class="signature-row">
        <div class="signature-box">Authorized Signature (Hospital)</div>
        <div class="signature-box">Supplier Acknowledgment</div>
    </div>
</div>

</body>
</html>