<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']); // Pharmacist and admin only

$sql = "SELECT r.receiving_id, m.medicine_name, s.supplier_name, b.batch_no, b.expiry_date,
               r.quantity_received, r.received_date
        FROM medicine_receiving r
        JOIN medicines m ON r.medicine_id = m.medicine_id
        JOIN suppliers s ON r.supplier_id = s.supplier_id
        LEFT JOIN medicine_batches b ON r.batch_id = b.batch_id
        ORDER BY r.receiving_id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receive Medicines - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="photo-medicines">

<div class="topbar">
    <h2>Receive Medicines</h2>
    <a href="dashboard.php" class="back">&larr; Back to Dashboard</a>
</div>

<div class="container">
    <div class="header-row">
        <h3>Receiving History</h3>
        <a href="add_receiving.php" class="btn">+ Receive New Stock</a>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Medicine</th>
            <th>Supplier</th>
            <th>Batch No</th>
            <th>Expiry Date</th>
            <th>Qty Received</th>
            <th>Received Date</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['receiving_id']; ?></td>
            <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
            <td><?php echo htmlspecialchars($row['batch_no']); ?></td>
            <td><?php echo $row['expiry_date']; ?></td>
            <td><?php echo $row['quantity_received']; ?></td>
            <td><?php echo $row['received_date']; ?></td>
            <td><a href="delivery_note.php?id=<?php echo $row['receiving_id']; ?>" class="btn btn-view" target="_blank">Print Note</a></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
