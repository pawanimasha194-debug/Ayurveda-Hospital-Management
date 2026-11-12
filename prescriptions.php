<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['doctor', 'pharmacist']); // Doctor and pharmacist can view prescriptions

$sql = "SELECT p.prescription_id, pt.full_name AS patient_name, d.full_name AS doctor_name,
               p.prescription_date, p.status
        FROM prescriptions p
        JOIN patients pt ON p.patient_id = pt.patient_id
        JOIN doctors d ON p.doctor_id = d.doctor_id
        ORDER BY p.prescription_id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Prescriptions - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="photo-prescriptions">

<div class="topbar">
    <h2>Prescriptions</h2>
    <a href="dashboard.php" class="back">&larr; Back to Dashboard</a>
</div>

<div class="container">
    <div class="header-row">
        <h3>Prescription Records</h3>
        <?php if ($_SESSION['role'] == 'doctor' || $_SESSION['role'] == 'admin'): ?>
        <a href="add_prescription.php" class="btn">+ New Prescription</a>
        <?php endif; ?>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Patient</th>
            <th>Doctor</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['prescription_id']; ?></td>
            <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
            <td><?php echo htmlspecialchars($row['doctor_name']); ?></td>
            <td><?php echo $row['prescription_date']; ?></td>
            <td><span class="status-badge status-<?php echo str_replace(' ', '', $row['status']); ?>"><?php echo $row['status']; ?></span></td>
            <td><a href="view_prescription.php?id=<?php echo $row['prescription_id']; ?>" class="btn btn-view">View</a></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
