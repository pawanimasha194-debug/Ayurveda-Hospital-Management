<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']);

// Get all prescriptions that are Pending or Partially Issued
// ONLY those that have at least one in-house medicine
$sql = "SELECT DISTINCT p.prescription_id, p.prescription_date, p.status,
               pt.full_name AS patient_name, pt.contact_number,
               d.full_name AS doctor_name
        FROM prescriptions p
        JOIN patients pt ON p.patient_id = pt.patient_id
        JOIN doctors d ON p.doctor_id = d.doctor_id
        JOIN prescription_details pd ON pd.prescription_id = p.prescription_id
        WHERE p.status IN ('Pending', 'Partially Issued')
          AND pd.dispense_type = 'inhouse'
        ORDER BY p.prescription_date ASC, p.prescription_id ASC";
$prescriptionsResult = $conn->query($sql);

$prescriptions = [];
while ($row = $prescriptionsResult->fetch_assoc()) {
    $prescriptions[] = $row;
}

// Fetch ONLY in-house medicine lines
function getMedicineLines($conn, $prescription_id) {
    $sql = "SELECT pd.prescribed_quantity, pd.dosage_instructions,
                   m.medicine_name, m.unit, m.current_stock,
                   COALESCE((SELECT SUM(quantity_issued) FROM medicine_issuing mi WHERE mi.prescription_id = pd.prescription_id AND mi.medicine_id = pd.medicine_id), 0) AS already_issued
            FROM prescription_details pd
            JOIN medicines m ON pd.medicine_id = m.medicine_id
            WHERE pd.prescription_id = ? AND pd.dispense_type = 'inhouse'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $prescription_id);
    $stmt->execute();
    return $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pharmacist - Pending Issues - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="photo-prescriptions">

<div class="topbar">
    <h2>Pharmacist &ndash; Pending Medicine Issues</h2>
    <a href="dashboard.php">&larr; Back to Dashboard</a>
</div>

<div class="container">
    <div class="header-row">
        <h3>Prescriptions Waiting to be Issued</h3>
    </div>

    <p class="info-note">Only prescriptions with <b>in-house</b> medicines are shown here. External medicines are handled separately by the patient at an outside pharmacy.</p>

    <?php if (count($prescriptions) == 0): ?>
        <div class="empty-msg">No pending prescriptions. All medicines have been issued.</div>
    <?php else: ?>
        <?php foreach ($prescriptions as $p): ?>
        <div class="presc-card">
            <div class="presc-header">
                <div class="info">
                    <b>#<?php echo $p['prescription_id']; ?> &mdash; <?php echo htmlspecialchars($p['patient_name']); ?></b><br>
                    Doctor: <?php echo htmlspecialchars($p['doctor_name']); ?> &nbsp;|&nbsp; Date: <?php echo $p['prescription_date']; ?>
                </div>
                <div>
                    <span class="status-badge status-<?php echo str_replace(' ', '', $p['status']); ?>"><?php echo $p['status']; ?></span>
                </div>
            </div>
            <table>
                <tr>
                    <th>Medicine</th>
                    <th>Prescribed</th>
                    <th>Already Issued</th>
                    <th>Remaining</th>
                    <th>Stock Available</th>
                </tr>
                <?php
                $medLines = getMedicineLines($conn, $p['prescription_id']);
                while ($m = $medLines->fetch_assoc()):
                    $remaining = $m['prescribed_quantity'] - $m['already_issued'];
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($m['medicine_name']); ?></td>
                    <td><?php echo $m['prescribed_quantity']; ?> <?php echo htmlspecialchars($m['unit']); ?></td>
                    <td><?php echo $m['already_issued']; ?></td>
                    <td>
                        <?php if ($remaining <= 0): ?>
                            <span class="done-tag">Fully Issued</span>
                        <?php else: ?>
                            <?php echo $remaining; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo $m['current_stock']; ?>
                        <?php if ($m['current_stock'] < $remaining): ?>
                            <div class="low-warn">Insufficient stock!</div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
            <div style="padding: 14px 20px;">
                <a href="issue_medicine.php?prescription_id=<?php echo $p['prescription_id']; ?>" class="btn">Issue Medicines</a>
                <a href="view_prescription.php?id=<?php echo $p['prescription_id']; ?>" class="btn" style="background:#2980b9; margin-left:8px;">View Full Prescription</a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>