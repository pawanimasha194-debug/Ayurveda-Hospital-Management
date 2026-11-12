<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']);

// Delete PO
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM purchase_orders WHERE po_id = $id");
    header("Location: purchase_orders.php");
    exit();
}

$sql = "SELECT po.*, s.supplier_name, u.full_name AS created_by_name 
        FROM purchase_orders po
        JOIN suppliers s ON po.supplier_id = s.supplier_id
        LEFT JOIN users u ON po.created_by = u.user_id
        ORDER BY po.po_id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Purchase Orders - Ayurveda Hospital</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.header-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
</style>
</head>
<body class="photo-medicines">

<div class="topbar">
    <h2>Purchase Orders</h2>
    <a href="dashboard.php" class="back">&larr; Back to Dashboard</a>
</div>

<div class="container">
    <div class="header-row">
        <h3>Order Records</h3>
        <div class="header-actions">
            <a href="low_stock_order_list.php" class="btn" style="background:#ff9800; color:#fff;">⚡ View Auto Order List</a>
            <a href="add_purchase_order.php" class="btn">+ Create Manual Order</a>
        </div>
    </div>

    <table>
        <tr>
            <th>PO No</th>
            <th>Supplier</th>
            <th>Order Date</th>
            <th>Status</th>
            <th>Created By</th>
            <th>Actions</th>
        </tr>
        <?php if ($result->num_rows == 0): ?>
            <tr><td colspan="6" style="text-align:center; color:#888;">No purchase orders created yet.</td></tr>
        <?php else: while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><b>#PO-<?php echo str_pad($row['po_id'], 4, '0', STR_PAD_LEFT); ?></b></td>
            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
            <td><?php echo date('d M Y', strtotime($row['order_date'])); ?></td>
            <td>
                <span class="status-badge status-<?php echo $row['status']; ?>">
                    <?php echo $row['status']; ?>
                </span>
            </td>
            <td><?php echo htmlspecialchars($row['created_by_name']); ?></td>
            <td class="actions">
                <a href="print_purchase_order.php?id=<?php echo $row['po_id']; ?>" class="btn" style="background:#c9a227; color:#1b5e20;" target="_blank">Print PO</a>
                <a href="purchase_orders.php?delete=<?php echo $row['po_id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this purchase order?');">Delete</a>
            </td>
        </tr>
        <?php endwhile; endif; ?>
    </table>
</div>

</body>
</html>