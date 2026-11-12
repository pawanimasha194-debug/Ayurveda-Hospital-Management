<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']); // Only pharmacists handle issuing

// Get all prescriptions that are Pending or Partially Issued
$sql = "SELECT p.prescription_id, p.prescription_date, p.status,
               pt.full_name AS patient_name, pt.contact_number,
               d.full_name AS doctor_name
        FROM prescriptions p
        JOIN patients pt ON p.patient_id = pt.patient_id
        JOIN doctors d ON p.doctor_id = d.doctor_id
        WHERE p.status IN ('Pending', 'Partially Issued')
        ORDER BY p.prescription_date ASC, p.prescription_id ASC";
$prescriptionsResult = $conn->query($sql);

// Collect prescription IDs to fetch their medicine lines in one go
$prescriptions = [];
while ($row = $prescriptionsResult->fetch_assoc()) {
    $prescriptions[] = $row;
}

// Fetch medicine details for each prescription
function getMedicineLines($conn, $prescription_id) {
    $sql = "SELECT pd.prescribed_quantity, pd.dosage_instructions,
                   m.medicine_name, m.unit, m.current_stock,
                   COALESCE((SELECT SUM(quantity_issued) FROM medicine_issuing mi WHERE mi.prescription_id = pd.prescription_id AND mi.medicine_id = pd.medicine_id), 0) AS already_issued
            FROM prescription_details pd
            JOIN medicines m ON pd.medicine_id = m.medicine_id
            WHERE pd.prescription_id = ?";
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
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: #f2f5f0; margin: 0; }
    .topbar {
        background: #2e5339; color: white; padding: 15px 30px;
        display: flex; justify-content: space-between; align-items: center;
    }
    .topbar a { color: white; text-decoration: none; font-size: 14px; }
    .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
    .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .header-row h3 { color: #2e5339; margin: 0; }

    .presc-card {
        background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 20px; overflow: hidden;
    }
    .presc-header {
        background: #eef3ec; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center;
    }
    .presc-header .info { font-size: 14px; color: #2e5339; }
    .presc-header .info b { font-size: 15px; }
    .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }
    .status-Pending { background: #fdebd0; color: #9c640c; }
    .status-Partially.Issued, .status-PartiallyIssued { background: #d6eaf8; color: #21618c; }

    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 10px 20px; text-align: left; font-size: 14px; border-bottom: 1px solid #f0f0f0; }
    th { background: #fafafa; color: #555; font-size: 12px; text-transform: uppercase; }
    .low-warn { color: #c0392b; font-size: 12px; }
    .done-tag { color: #1e8449; font-weight: bold; font-size: 13px; }

    .btn {
        background: #2e5339; color: white; padding: 8px 16px; border-radius: 5px;
        text-decoration: none; font-size: 13px; border: none; cursor: pointer; display: inline-block;
    }
    .btn:hover { background: #244129; }
    .empty-msg { color: #888; font-size: 15px; text-align: center; padding: 40px 0; background: white; border-radius: 10px; }
</style>
</head>
<body>

<div class="topbar">
    <h2>Pharmacist &ndash; Pending Medicine Issues</h2>
    <a href="dashboard.php">&larr; Back to Dashboard</a>
</div>

<div class="container">
    <div class="header-row">
        <h3>Prescriptions Waiting to be Issued</h3>
    </div>

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
