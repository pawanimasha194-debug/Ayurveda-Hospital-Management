<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['doctor', 'pharmacist']);

if (!isset($_GET['id'])) {
    header("Location: prescriptions.php");
    exit();
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT p.*, pt.full_name AS patient_name, pt.contact_number AS patient_contact,
                                d.full_name AS doctor_name, d.specialization
                         FROM prescriptions p
                         JOIN patients pt ON p.patient_id = pt.patient_id
                         JOIN doctors d ON p.doctor_id = d.doctor_id
                         WHERE p.prescription_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) { header("Location: prescriptions.php"); exit(); }
$prescription = $result->fetch_assoc();

$stmt2 = $conn->prepare("SELECT pd.*, m.medicine_name, m.medicine_name_si, m.unit, m.current_stock
                          FROM prescription_details pd
                          JOIN medicines m ON pd.medicine_id = m.medicine_id
                          WHERE pd.prescription_id = ?
                          ORDER BY pd.dispense_type ASC, pd.detail_id ASC");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$details = $stmt2->get_result();

$ext_count = $conn->query("SELECT COUNT(*) c FROM prescription_details WHERE prescription_id = $id AND dispense_type='external'")->fetch_assoc()['c'];
$in_count  = $conn->query("SELECT COUNT(*) c FROM prescription_details WHERE prescription_id = $id AND dispense_type='inhouse'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Prescription #<?php echo $id; ?> - ගම්පහ බෙහෙත් ශාලාව</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.dispense-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; letter-spacing: 0.3px; white-space: nowrap; }
.dispense-badge.inhouse { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
.dispense-badge.external { background: #fff8e1; color: #8a6d1a; border: 1px solid #ffb300; }
</style>
</head>
<body class="photo-prescriptions">

<div class="topbar">
    <h2>Prescription #<?php echo $id; ?></h2>
    <a href="prescriptions.php">&larr; Back to Prescriptions</a>
</div>

<div class="container">
    <div class="card">
        <h3>Prescription Info</h3>
        <div class="info-grid">
            <div><span>Patient:</span> <?php echo htmlspecialchars($prescription['patient_name']); ?></div>
            <div><span>Doctor:</span> <?php echo htmlspecialchars($prescription['doctor_name']); ?></div>
            <div><span>Date:</span> <?php echo $prescription['prescription_date']; ?></div>
            <div><span>Status:</span> <span class="status-badge status-<?php echo str_replace(' ','',$prescription['status']); ?>"><?php echo $prescription['status']; ?></span></div>
            <div><span>Notes:</span> <?php echo htmlspecialchars($prescription['notes']); ?></div>
        </div>
    </div>

    <div class="card">
        <h3>Prescribed Medicines</h3>
        <table>
            <tr>
                <th>Medicine</th>
                <th>Prescribed Qty</th>
                <th>Unit</th>
                <th>Dosage Instructions</th>
                <th>Dispense From</th>
                <th>Current Stock</th>
            </tr>
            <?php while ($row = $details->fetch_assoc()): ?>
            <tr>
                <td>
                    <?php echo htmlspecialchars($row['medicine_name']); ?>
                    <?php if (!empty($row['medicine_name_si'])): ?>
                        <br><span style="font-size: 13px; color: #5f6f68;"><?php echo htmlspecialchars($row['medicine_name_si']); ?></span>
                    <?php endif; ?>
                </td>
                <td><?php echo $row['prescribed_quantity']; ?></td>
                <td><?php echo htmlspecialchars($row['unit']); ?></td>
                <td><?php echo htmlspecialchars($row['dosage_instructions']); ?></td>
                <td>
                    <?php if ($row['dispense_type'] == 'external'): ?>
                        <span class="dispense-badge external">📤 EXTERNAL</span>
                    <?php else: ?>
                        <span class="dispense-badge inhouse">🏥 IN-HOUSE</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($row['dispense_type'] == 'inhouse'): ?>
                        <?php echo $row['current_stock']; ?>
                    <?php else: ?>
                        <span style="color:#a5b0aa;">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

        <div style="margin-top:18px; display:flex; gap:10px; flex-wrap:wrap;">
            <?php if ($ext_count > 0): ?>
                <a href="print_external_rx.php?id=<?php echo $id; ?>" class="btn" style="background:#c9a227; color:#1b5e20;" target="_blank">🖨 Print External Pharmacy Slip (<?php echo $ext_count; ?>)</a>
            <?php endif; ?>

            <?php if ($prescription['status'] != 'Issued' && $in_count > 0 && ($_SESSION['role'] == 'pharmacist' || $_SESSION['role'] == 'admin')): ?>
                <a href="issue_medicine.php?prescription_id=<?php echo $id; ?>" class="btn">💊 Issue In-House Medicines (<?php echo $in_count; ?>)</a>
            <?php endif; ?>

            <!-- Print Bill Button -->
            <?php if ($prescription['status'] == 'Issued' || $prescription['status'] == 'Partially Issued'): ?>
                <a href="print_bill.php?id=<?php echo $id; ?>" class="btn" style="background:#1b5e20; color:#fff;" target="_blank">
                    🧾 Print Bill (Invoice)
                </a>
            <?php endif; ?>
        </div>

        <?php if ($ext_count > 0 && $in_count == 0): ?>
            <div class="info-note" style="margin-top:16px; background:#fff8e1; border-left:4px solid #c9a227; padding:12px 16px; border-radius:6px; color:#5f6f68;">
                ℹ️ All medicines in this prescription are marked as <b>External</b>. The patient should take the printed slip to an outside pharmacy.
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>