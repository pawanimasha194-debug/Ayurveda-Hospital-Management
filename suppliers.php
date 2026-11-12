<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']); // Pharmacist and admin only

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search != '') {
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE supplier_name LIKE ? OR contact_person LIKE ? ORDER BY supplier_id DESC");
    $likeSearch = "%$search%";
    $stmt->bind_param("ss", $likeSearch, $likeSearch);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM suppliers ORDER BY supplier_id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Suppliers - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="photo-medicines">

<div class="topbar">
    <h2>Suppliers</h2>
    <a href="dashboard.php" class="back">&larr; Back to Dashboard</a>
</div>

<div class="container">
    <div class="header-row">
        <h3>Supplier Records</h3>
        <a href="add_supplier.php" class="btn">+ Add New Supplier</a>
    </div>

    <form method="GET" class="search-box">
        <input type="text" name="search" placeholder="Search by supplier or contact person..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn">Search</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Supplier Name</th>
            <th>Contact Person</th>
            <th>Contact Number</th>
            <th>Email</th>
            <th>Address</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['supplier_id']; ?></td>
            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
            <td><?php echo htmlspecialchars($row['contact_person']); ?></td>
            <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['address']); ?></td>
            <td class="actions">
                <a href="edit_supplier.php?id=<?php echo $row['supplier_id']; ?>" class="btn btn-edit">Edit</a>
                <a href="delete_supplier.php?id=<?php echo $row['supplier_id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this supplier?');">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
