<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['doctor']); // Doctor and admin only

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search != '') {
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE full_name LIKE ? OR specialization LIKE ? ORDER BY doctor_id DESC");
    $likeSearch = "%$search%";
    $stmt->bind_param("ss", $likeSearch, $likeSearch);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM doctors ORDER BY doctor_id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctors - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="photo-people">

<div class="topbar">
    <h2>Doctors</h2>
    <a href="dashboard.php" class="back">&larr; Back to Dashboard</a>
</div>

<div class="container">
    <div class="header-row">
        <h3>Doctor Records</h3>
        <a href="add_doctor.php" class="btn">+ Add New Doctor</a>
    </div>

    <form method="GET" class="search-box">
        <input type="text" name="search" placeholder="Search by name or specialization..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn">Search</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Specialization</th>
            <th>Contact</th>
            <th>Email</th>
            <th>Registration No</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['doctor_id']; ?></td>
            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
            <td><?php echo htmlspecialchars($row['specialization']); ?></td>
            <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['registration_no']); ?></td>
            <td class="actions">
                <a href="edit_doctor.php?id=<?php echo $row['doctor_id']; ?>" class="btn btn-edit">Edit</a>
                <a href="delete_doctor.php?id=<?php echo $row['doctor_id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this doctor?');">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
