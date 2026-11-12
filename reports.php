<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. Low stock medicines
$lowStock = $conn->query("SELECT medicine_name, unit, current_stock, reorder_level
                           FROM medicines
                           WHERE current_stock <= reorder_level
                           ORDER BY current_stock ASC");

// 2. Expiring soon (within 90 days) and already expired batches
$expiring = $conn->query("SELECT m.medicine_name, b.batch_no, b.quantity, b.expiry_date,
                                  DATEDIFF(b.expiry_date, CURDATE()) AS days_left
                           FROM medicine_batches b
                           JOIN medicines m ON b.medicine_id = m.medicine_id
                           WHERE b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
                           ORDER BY b.expiry_date ASC");

// 3. Prescription summary (counts by status)
$prescriptionSummary = $conn->query("SELECT status, COUNT(*) AS total FROM prescriptions GROUP BY status");

// 4. Recent medicine issuing activity
$recentIssuing = $conn->query("SELECT mi.issued_date, m.medicine_name, mi.quantity_issued, pt.full_name AS patient_name
                                FROM medicine_issuing mi
                                JOIN medicines m ON mi.medicine_id = m.medicine_id
                                JOIN prescriptions p ON mi.prescription_id = p.prescription_id
                                JOIN patients pt ON p.patient_id = pt.patient_id
                                ORDER BY mi.issuing_id DESC LIMIT 10");

// 5. Inventory value summary
$inventoryValue = $conn->query("SELECT SUM(current_stock * unit_price) AS total_value, COUNT(*) AS total_medicines FROM medicines")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reports - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="topbar">
    <h2>Reports</h2>
    <div>
        <a href="inventory_report.php" target="_blank" style="margin-right:15px;">🖨 Print Inventory Report</a>
        <a href="dashboard.php">&larr; Back to Dashboard</a>
    </div>
</div>

<div class="container">

    <div class="summary-row">
        <div class="summary-card">
            <div class="value"><?php echo $inventoryValue['total_medicines']; ?></div>
            <div class="label">Total Medicines Tracked</div>
        </div>
        <div class="summary-card">
            <div class="value">Rs. <?php echo number_format($inventoryValue['total_value'] ?? 0, 2); ?></div>
            <div class="label">Total Stock Value</div>
        </div>
        <div class="summary-card">
            <div class="value"><?php echo $lowStock->num_rows; ?></div>
            <div class="label">Low Stock Medicines</div>
        </div>
        <div class="summary-card">
            <div class="value"><?php echo $expiring->num_rows; ?></div>
            <div class="label">Batches Expiring / Expired</div>
        </div>
    </div>

    <div class="card">
        <h3>Low Stock Medicines</h3>
        <?php if ($lowStock->num_rows == 0): ?>
            <div class="empty-msg">No medicines below reorder level.</div>
        <?php else: ?>
        <table>
            <tr><th>Medicine</th><th>Unit</th><th>Current Stock</th><th>Reorder Level</th></tr>
            <?php while ($row = $lowStock->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
                <td><?php echo htmlspecialchars($row['unit']); ?></td>
                <td><?php echo $row['current_stock']; ?></td>
                <td><?php echo $row['reorder_level']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Expiry Monitoring (next 90 days + already expired)</h3>
        <?php if ($expiring->num_rows == 0): ?>
            <div class="empty-msg">No batches expiring soon.</div>
        <?php else: ?>
        <table>
            <tr><th>Medicine</th><th>Batch No</th><th>Quantity</th><th>Expiry Date</th><th>Status</th></tr>
            <?php while ($row = $expiring->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
                <td><?php echo htmlspecialchars($row['batch_no']); ?></td>
                <td><?php echo $row['quantity']; ?></td>
                <td><?php echo $row['expiry_date']; ?></td>
                <td>
                    <?php if ($row['days_left'] < 0): ?>
                        <span class="badge badge-expired">Expired</span>
                    <?php else: ?>
                        <span class="badge badge-soon"><?php echo $row['days_left']; ?> days left</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Prescription Summary</h3>
        <?php if ($prescriptionSummary->num_rows == 0): ?>
            <div class="empty-msg">No prescriptions recorded yet.</div>
        <?php else: ?>
        <table>
            <tr><th>Status</th><th>Count</th></tr>
            <?php while ($row = $prescriptionSummary->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['status']; ?></td>
                <td><?php echo $row['total']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Recent Medicine Issuing Activity</h3>
        <?php if ($recentIssuing->num_rows == 0): ?>
            <div class="empty-msg">No medicines issued yet.</div>
        <?php else: ?>
        <table>
            <tr><th>Date</th><th>Medicine</th><th>Qty Issued</th><th>Patient</th></tr>
            <?php while ($row = $recentIssuing->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['issued_date']; ?></td>
                <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
                <td><?php echo $row['quantity_issued']; ?></td>
                <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
