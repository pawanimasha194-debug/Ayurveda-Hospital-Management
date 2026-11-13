<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']);

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$baseQuery = "SELECT m.*, nb.expiry_date AS nearest_expiry, nb.batch_no, nb.received_date AS last_received, s.supplier_name
              FROM medicines m
              LEFT JOIN medicine_batches nb ON nb.batch_id = (
                  SELECT b2.batch_id FROM medicine_batches b2
                  WHERE b2.medicine_id = m.medicine_id AND b2.quantity > 0
                  ORDER BY b2.expiry_date ASC LIMIT 1
              )
              LEFT JOIN suppliers s ON s.supplier_id = nb.supplier_id
              ";

if ($search != '') {
    $stmt = $conn->prepare($baseQuery . " WHERE m.medicine_name LIKE ? OR m.medicine_name_si LIKE ? OR m.medicine_type LIKE ? ORDER BY m.medicine_id DESC");
    $likeSearch = "%$search%";
    $stmt->bind_param("sss", $likeSearch, $likeSearch, $likeSearch);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($baseQuery . " ORDER BY m.medicine_id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Medicines - ගම්පහ බෙහෙත් ශාලාව</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<script src="theme-toggle.js"></script>
</head>
<body class="photo-medicines">

<div class="topbar">
    <h2>Medicines & Stock</h2>
    <a href="dashboard.php" class="back">&larr; Back to Dashboard</a>
</div>

<div class="container">
    <div class="header-row">
        <h3>Medicine Records</h3>
        <a href="add_medicine.php" class="btn">+ Add New Medicine</a>
    </div>

    <form method="GET" class="search-box">
        <input type="text" name="search" placeholder="Search by name (English/Sinhala) or type..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn">Search</button>
    </form>

    <div class="legend"><span></span> Row highlighted = stock at or below reorder level (low stock)</div>

    <table>
        <tr>
            <th>Med ID</th>
            <th>Name</th>
            <th>Type</th>
            <th>Current Stock</th>
            <th>Nearest Expiry</th>
            <th>Batch No</th>
            <th>Supplier</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()):
            $lowStock = ($row['current_stock'] <= $row['reorder_level']);
            $expiry = $row['nearest_expiry'];
            $expiryClass = '';
            $expiryLabel = 'No batch data';
            if ($expiry) {
                $daysLeft = (strtotime($expiry) - strtotime(date('Y-m-d'))) / 86400;
                if ($daysLeft < 0) {
                    $expiryClass = 'badge-expired';
                    $expiryLabel = 'Expired (' . $expiry . ')';
                } elseif ($daysLeft <= 90) {
                    $expiryClass = 'badge-soon';
                    $expiryLabel = $expiry . ' (' . intval($daysLeft) . ' days)';
                } else {
                    $expiryLabel = $expiry;
                }
            }
        ?>
        <tr class="<?php echo $lowStock ? 'low-stock' : ''; ?>">
            <!-- Custom Medicine Code -->
            <td><b>MED-<?php echo str_pad($row['medicine_id'], 3, '0', STR_PAD_LEFT); ?></b></td>
            <td>
                <?php echo htmlspecialchars($row['medicine_name']); ?>
                <?php if (!empty($row['medicine_name_si'])): ?>
                    <br><span style="font-size: 13px; color: #5f6f68;"><?php echo htmlspecialchars($row['medicine_name_si']); ?></span>
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($row['medicine_type']); ?></td>
            <td><?php echo $row['current_stock']; ?> <?php echo htmlspecialchars($row['unit']); ?></td>
            <td><?php if ($expiryClass): ?><span class="badge <?php echo $expiryClass; ?>"><?php echo $expiryLabel; ?></span><?php else: ?><?php echo $expiryLabel; ?><?php endif; ?></td>
            <!-- Custom Batch Code -->
            <td><span style="font-family: monospace; font-weight: bold; color: #c9a227;"><?php echo htmlspecialchars($row['batch_no'] ?? '-'); ?></span></td>
            <td><?php echo htmlspecialchars($row['supplier_name'] ?? '-'); ?></td>
            <td class="actions">
                <a href="edit_medicine.php?id=<?php echo $row['medicine_id']; ?>" class="btn btn-edit">Edit</a>
                <a href="delete_medicine.php?id=<?php echo $row['medicine_id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this medicine?');">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>