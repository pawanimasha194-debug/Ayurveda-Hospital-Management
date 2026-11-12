<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['doctor']); // Doctor and admin only

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search != '') {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE full_name LIKE ? OR nic_number LIKE ? ORDER BY patient_id DESC");
    $likeSearch = "%$search%";
    $stmt->bind_param("ss", $likeSearch, $likeSearch);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM patients ORDER BY patient_id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patients - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="photo-people">

<div class="topbar">
    <h2>Patients</h2>
    <a href="dashboard.php" class="back">&larr; Back to Dashboard</a>
</div>

<div class="container">
    <div class="header-row">
        <h3>Patient Records</h3>
        <a href="add_patient.php" class="btn">+ Add New Patient</a>
    </div>

    <form method="GET" class="search-box">
        <input type="text" name="search" placeholder="Search by name or NIC..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn">Search</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>DOB</th>
            <th>Gender</th>
            <th>Contact</th>
            <th>NIC</th>
            <th>Address</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['patient_id']; ?></td>
            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
            <td><?php echo $row['dob']; ?></td>
            <td><?php echo $row['gender']; ?></td>
            <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
            <td><?php echo htmlspecialchars($row['nic_number']); ?></td>
            <td><?php echo htmlspecialchars($row['address']); ?></td>
            <td class="actions">
                <a href="edit_patient.php?id=<?php echo $row['patient_id']; ?>" class="btn btn-edit">Edit</a>
                <a href="delete_patient.php?id=<?php echo $row['patient_id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this patient?');">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
