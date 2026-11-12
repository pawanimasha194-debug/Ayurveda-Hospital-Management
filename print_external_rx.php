<?php
include 'config.php';
include 'hospital_info.php';

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

$stmt = $conn->prepare("SELECT p.*, pt.full_name AS patient_name, pt.contact_number AS patient_phone,
                                pt.nic_number, pt.address, pt.dob, pt.gender,
                                d.full_name AS doctor_name, d.specialization, d.registration_no
                         FROM prescriptions p
                         JOIN patients pt ON p.patient_id = pt.patient_id
                         JOIN doctors d ON p.doctor_id = d.doctor_id
                         WHERE p.prescription_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$rx = $stmt->get_result()->fetch_assoc();

if (!$rx) { header("Location: prescriptions.php"); exit(); }

// ALUTH: medicine_name_si ekath fetch karanawa
$stmt2 = $conn->prepare("SELECT pd.*, m.medicine_name, m.medicine_name_si, m.unit, m.medicine_type
                          FROM prescription_details pd
                          JOIN medicines m ON pd.medicine_id = m.medicine_id
                          WHERE pd.prescription_id = ? AND pd.dispense_type = 'external'
                          ORDER BY pd.detail_id ASC");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$external = $stmt2->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>External Pharmacy Prescription - <?php echo htmlspecialchars($hospital_name_en); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.rx-slip-page { max-width: 800px; margin: 30px auto; background: #fff; padding: 40px 46px; border: 1px solid #dfece0; border-radius: 10px; box-shadow: 0 8px 30px rgba(27,94,32,0.08); }
.rx-title { text-align: center; font-family: 'Zilla Slab', serif; font-size: 22px; color: #1b5e20; margin: 6px 0 4px; letter-spacing: 2px; text-transform: uppercase; }
.rx-subtitle { text-align: center; font-size: 13px; color: #5f6f68; margin-bottom: 22px; letter-spacing: 1px; text-transform: uppercase; }
.rx-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 30px; background: #f8fbf7; border: 1px solid #dfece0; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px; font-size: 14px; }
.rx-meta-grid .row { display: flex; gap: 8px; }
.rx-meta-grid .row .k { color: #5f6f68; font-weight: 700; min-width: 90px; }
.rx-meta-grid .row .v { color: #1b5e20; font-weight: 500; }
.rx-meta-grid .full { grid-column: 1 / -1; }
.rx-section-title { font-family: 'Zilla Slab', serif; color: #1b5e20; font-size: 16px; margin: 22px 0 10px; padding-bottom: 6px; border-bottom: 2px solid #c9a227; display: inline-block; letter-spacing: 1px; text-transform: uppercase; }
.rx-med-table { width: 100%; border-collapse: collapse; margin-top: 4px; margin-bottom: 8px; }
.rx-med-table th { background: #1b5e20; color: #fff; padding: 10px 12px; text-align: left; font-size: 12.5px; text-transform: uppercase; letter-spacing: 0.8px; }
.rx-med-table th:first-child { width: 40px; text-align: center; }
.rx-med-table td { padding: 12px; border-bottom: 1px solid #dfece0; font-size: 14.5px; color: #263238; vertical-align: top; }
.rx-med-table td:first-child { text-align: center; font-family: 'Zilla Slab', serif; font-weight: 700; color: #1b5e20; font-size: 16px; }
.rx-med-table td .med-name { font-weight: 700; color: #1b5e20; }
.rx-med-table td .med-type { font-size: 12px; color: #5f6f68; font-style: italic; display: block; margin-top: 2px; }
.rx-med-table tr:nth-child(even) td { background: #f8fbf7; }
.pharmacy-note { display: inline-block; margin-top: 6px; background: #fff8e1; border-left: 3px solid #c9a227; padding: 5px 10px; border-radius: 5px; font-size: 12.5px; color: #8a6d1a; font-style: italic; line-height: 1.4; }
.pharmacy-note::before { content: '📝 '; font-style: normal; }
.rx-dosage { font-size: 13.5px; color: #263238; }
.rx-notes { background: #fff8e1; border-left: 4px solid #c9a227; padding: 12px 16px; border-radius: 6px; font-size: 13.5px; color: #5f6f68; margin-top: 18px; }
.rx-notes b { color: #1b5e20; }
.rx-footer { margin-top: 60px; display: grid; grid-template-columns: 1fr 1fr; gap: 60px; }
.rx-sig-box { border-top: 1.5px solid #263238; padding-top: 8px; font-size: 13px; text-align: center; color: #5f6f68; }
.rx-sig-box b { color: #1b5e20; display: block; margin-bottom: 4px; }
.rx-watermark { text-align: center; font-size: 11px; color: #a5b0aa; margin-top: 30px; font-style: italic; }
.rx-actions { max-width: 800px; margin: 0 auto 8px; text-align: right; }
@media print {
    body { background: #fff !important; }
    .topbar, .rx-actions { display: none !important; }
    .rx-slip-page { border: none; box-shadow: none; margin: 0; padding: 10px 30px; max-width: 100%; }
}
</style>
</head>
<body class="photo-prescriptions">

<div class="topbar">
    <h2>External Pharmacy Prescription</h2>
    <a href="view_prescription.php?id=<?php echo $id; ?>">&larr; Back to Prescription</a>
</div>

<div class="rx-actions">
    <button onclick="window.print()" class="btn" style="background:#1b5e20;">🖨 Print</button>
</div>

<div class="rx-slip-page">

    <?php renderLetterhead(); ?>

    <h2 class="rx-title">Prescription for External Pharmacy</h2>
    <p class="rx-subtitle">Rx No: P-<?php echo str_pad($rx['prescription_id'], 5, '0', STR_PAD_LEFT); ?></p>

    <div class="rx-meta-grid">
        <div class="row"><span class="k">Patient:</span><span class="v"><?php echo htmlspecialchars($rx['patient_name']); ?></span></div>
        <div class="row"><span class="k">Date:</span><span class="v"><?php echo date('d M Y', strtotime($rx['prescription_date'])); ?></span></div>
        <div class="row"><span class="k">Phone:</span><span class="v"><?php echo htmlspecialchars($rx['patient_phone'] ?: '-'); ?></span></div>
        <div class="row"><span class="k">NIC:</span><span class="v"><?php echo htmlspecialchars($rx['nic_number'] ?: '-'); ?></span></div>
        <?php if ($rx['dob'] || $rx['gender']): ?>
        <div class="row"><span class="k">DOB:</span><span class="v"><?php echo htmlspecialchars($rx['dob'] ?: '-'); ?></span></div>
        <div class="row"><span class="k">Gender:</span><span class="v"><?php echo htmlspecialchars($rx['gender'] ?: '-'); ?></span></div>
        <?php endif; ?>
        <?php if ($rx['address']): ?>
        <div class="row full"><span class="k">Address:</span><span class="v"><?php echo htmlspecialchars($rx['address']); ?></span></div>
        <?php endif; ?>
    </div>

    <div class="rx-section-title">Prescribed Medicines</div>

    <?php if ($external->num_rows == 0): ?>
        <p style="color:#888;font-style:italic;">No external medicines were prescribed for this patient.</p>
    <?php else: ?>
    <table class="rx-med-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Medicine</th>
                <th>Quantity</th>
                <th>Dosage Instructions</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; while ($m = $external->fetch_assoc()): ?>
            <tr>
                <td><?php echo $i++; ?></td>
                <td>
                    <span class="med-name"><?php echo htmlspecialchars($m['medicine_name']); ?></span>
                    <?php if (!empty($m['medicine_name_si'])): ?>
                        <!-- ALUTH: Slip eke Sinhala namath print karanawa -->
                        <span class="med-type"><?php echo htmlspecialchars($m['medicine_name_si']); ?></span>
                    <?php elseif ($m['medicine_type']): ?>
                        <span class="med-type"><?php echo htmlspecialchars($m['medicine_type']); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($m['external_note'])): ?>
                        <div class="pharmacy-note"><?php echo htmlspecialchars($m['external_note']); ?></div>
                    <?php endif; ?>
                </td>
                <td><b><?php echo $m['prescribed_quantity']; ?></b> <?php echo htmlspecialchars($m['unit']); ?></td>
                <td class="rx-dosage"><?php echo htmlspecialchars($m['dosage_instructions'] ?: '-'); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if (!empty($rx['notes'])): ?>
    <div class="rx-notes">
        <b>Doctor's Notes:</b> <?php echo htmlspecialchars($rx['notes']); ?>
    </div>
    <?php endif; ?>

    <div class="rx-footer">
        <div class="rx-sig-box">
            <b><?php echo htmlspecialchars($rx['doctor_name']); ?></b>
            <?php echo htmlspecialchars($rx['specialization'] ?: 'Ayurvedic Physician'); ?><br>
            Reg No: <?php echo htmlspecialchars($rx['registration_no'] ?: '-'); ?>
        </div>
        <div class="rx-sig-box">
            <b>Date</b>
            <?php echo date('d M Y', strtotime($rx['prescription_date'])); ?>
        </div>
    </div>

    <p class="rx-watermark">This prescription is valid for one-time dispensing only. Please dispense as prescribed.</p>

</div>

</body>
</html>