<?php
include 'config.php';
include 'hospital_info.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['doctor', 'pharmacist', 'reception']);

if (!isset($_GET['id'])) { header("Location: prescriptions.php"); exit(); }
$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT p.*, pt.patient_id, pt.full_name AS patient_name, pt.contact_number, pt.patient_type,
                               d.full_name AS doctor_name, d.consultation_fee
                        FROM prescriptions p
                        JOIN patients pt ON p.patient_id = pt.patient_id
                        JOIN doctors d ON p.doctor_id = d.doctor_id
                        WHERE p.prescription_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();

if (!$bill) { header("Location: prescriptions.php"); exit(); }

$pat_prefix = ($bill['patient_type'] == 'regular') ? 'R-' : 'T-';
$patient_code = $pat_prefix . str_pad($bill['patient_id'], 4, '0', STR_PAD_LEFT);

$med_query = "SELECT mi.quantity_issued, m.medicine_id, m.unit_price, m.medicine_name, m.medicine_name_si 
              FROM medicine_issuing mi 
              JOIN medicines m ON mi.medicine_id = m.medicine_id 
              WHERE mi.prescription_id = ?";
$stmt2 = $conn->prepare($med_query);
$stmt2->bind_param("i", $id);
$stmt2->execute();
$issued_meds = $stmt2->get_result();

$med_total = 0;
$medicines = [];
while ($m = $issued_meds->fetch_assoc()) {
    $line_total = $m['quantity_issued'] * $m['unit_price'];
    $med_total += $line_total;
    $m['line_total'] = $line_total;
    $medicines[] = $m;
}

$fee = $bill['consultation_fee'] ?? 0.00;
$grand_total = $med_total + $fee;

// ALUTH: බිල ඩේටාබේස් එකේ සේව් කිරීම (කලින් සේව් කරලා නැත්නම් විතරක්)
$bill_check = $conn->query("SELECT bill_id FROM bills WHERE prescription_id = $id");
if ($bill_check->num_rows == 0) {
    $stmt_bill = $conn->prepare("INSERT INTO bills (prescription_id, bill_date, total_amount, status) VALUES (?, CURDATE(), ?, 'Paid')");
    $stmt_bill->bind_param("id", $id, $grand_total);
    $stmt_bill->execute();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bill - P#<?php echo $id; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&family=Noto+Sans+Sinhala:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.bill-page { max-width: 800px; margin: 30px auto; background: #fff; padding: 40px 50px; border: 1px solid #dfece0; border-radius: 10px; box-shadow: 0 8px 30px rgba(27,94,32,0.08); }
.bill-title { text-align: center; font-family: 'Zilla Slab', serif; font-size: 22px; color: #1b5e20; margin: 10px 0; text-transform: uppercase; }
.bill-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 30px; background: #f8fbf7; padding: 16px 20px; border-radius: 8px; border: 1px solid #dfece0; margin-bottom: 24px; font-size: 14px; }
.bill-meta b { color: #1b5e20; }
.bill-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.bill-table th { background: #1b5e20; color: #fff; padding: 10px; text-align: left; font-size: 13px; }
.bill-table td { padding: 12px 10px; border-bottom: 1px solid #dfece0; font-size: 14px; }
.bill-table tr:nth-child(even) { background: #f8fbf7; }
.total-box { width: 300px; float: right; background: #f8fbf7; border: 1px solid #dfece0; border-radius: 8px; padding: 16px 20px; }
.total-row { display: flex; justify-content: space-between; font-size: 15px; margin-bottom: 8px; color: #5f6f68; }
.total-row.grand { border-top: 2px dashed #c8e6c9; padding-top: 12px; margin-top: 8px; font-size: 18px; font-weight: 700; color: #1b5e20; }
.signature-row { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-top: 80px; clear: both; }
.signature-box { border-top: 1px solid #263238; padding-top: 8px; text-align: center; font-size: 13px; color: #5f6f68; }
@media print { .topbar, .print-actions { display: none !important; } .bill-page { border: none; box-shadow: none; margin: 0; padding: 0; } }
</style>
</head>
<body class="photo-prescriptions">

<div class="topbar">
    <h2>Patient Bill</h2>
    <a href="javascript:history.back()">&larr; Back</a>
</div>

<div class="print-actions" style="max-width: 800px; margin: 0 auto 10px; text-align: right;">
    <button onclick="window.print()" class="btn" style="background:#1b5e20;">🖨 Print Bill</button>
</div>

<div class="bill-page">
    <?php renderLetterhead(); ?>

    <h2 class="bill-title">Patient Invoice / Bill</h2>

    <div class="bill-meta">
        <div><b>Bill No:</b> INV-<?php echo str_pad($bill['prescription_id'], 5, '0', STR_PAD_LEFT); ?></div>
        <div><b>Date:</b> <?php echo date('d M Y', strtotime($bill['prescription_date'])); ?></div>
        <div><b>Patient ID:</b> <?php echo $patient_code; ?></div>
        <div><b>Patient Name:</b> <?php echo htmlspecialchars($bill['patient_name']); ?></div>
        <div><b>Doctor:</b> <?php echo htmlspecialchars($bill['doctor_name']); ?></div>
    </div>

    <table class="bill-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><b>-</b></td>
                <td><b>Doctor Consultation Fee</b></td>
                <td>1</td>
                <td><?php echo number_format($fee, 2); ?></td>
                <td><?php echo number_format($fee, 2); ?></td>
            </tr>
            <?php foreach ($medicines as $m): ?>
            <tr>
                <td><b>MED-<?php echo str_pad($m['medicine_id'], 3, '0', STR_PAD_LEFT); ?></b></td>
                <td>
                    <?php echo htmlspecialchars($m['medicine_name']); ?>
                    <?php if($m['medicine_name_si']): ?>
                        <br><small style="font-family: 'Noto Sans Sinhala', sans-serif; color:#666;"><?php echo htmlspecialchars($m['medicine_name_si']); ?></small>
                    <?php endif; ?>
                </td>
                <td><?php echo $m['quantity_issued']; ?></td>
                <td><?php echo number_format($m['unit_price'], 2); ?></td>
                <td><?php echo number_format($m['line_total'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-box">
        <div class="total-row"><span>Consultation Fee:</span> <span>Rs. <?php echo number_format($fee, 2); ?></span></div>
        <div class="total-row"><span>Medicines Total:</span> <span>Rs. <?php echo number_format($med_total, 2); ?></span></div>
        <div class="total-row grand"><span>Grand Total:</span> <span>Rs. <?php echo number_format($grand_total, 2); ?></span></div>
    </div>

    <div class="signature-row">
        <div class="signature-box">Cashier / Pharmacist</div>
        <div class="signature-box">Patient Signature</div>
    </div>
</div>

</body>
</html>