<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']); // Pharmacist and admin only

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
    $stmt = $conn->prepare($baseQuery . " WHERE m.medicine_name LIKE ? OR m.medicine_type LIKE ? ORDER BY m.medicine_id DESC");
    $likeSearch = "%$search%";
    $stmt->bind_param("ss", $likeSearch, $likeSearch);
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
<title>Medicines - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
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
        <input type="text" name="search" placeholder="Search by name or type..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn">Search</button>
    </form>

    <div class="legend"><span></span> Row highlighted = stock at or below reorder level (low stock)</div>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Type</th>
            <th>Unit</th>
            <th>Current Stock</th>
            <th>Reorder Level</th>
            <th>Unit Price</th>
            <th>Nearest Expiry</th>
            <th>Batch No</th>
            <th>Supplier</th>
            <th>Last Received</th>
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
            <td><?php echo $row['medicine_id']; ?></td>
            <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
            <td><?php echo htmlspecialchars($row['medicine_type']); ?></td>
            <td><?php echo htmlspecialchars($row['unit']); ?></td>
            <td><?php echo $row['current_stock']; ?></td>
            <td><?php echo $row['reorder_level']; ?></td>
            <td><?php echo number_format($row['unit_price'], 2); ?></td>
            <td><?php if ($expiryClass): ?><span class="badge <?php echo $expiryClass; ?>"><?php echo $expiryLabel; ?></span><?php else: ?><?php echo $expiryLabel; ?><?php endif; ?></td>
            <td><?php echo htmlspecialchars($row['batch_no'] ?? '-'); ?></td>
            <td><?php echo htmlspecialchars($row['supplier_name'] ?? '-'); ?></td>
            <td><?php echo $row['last_received'] ?? '-'; ?></td>
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
