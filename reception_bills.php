<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['reception', 'admin']);

$today = date('Y-m-d');

// Fetch prescriptions that have medicines issued today
$sql = "SELECT p.prescription_id, p.prescription_date, p.status, 
               pt.full_name AS patient_name, 
               d.full_name AS doctor_name 
        FROM prescriptions p 
        JOIN patients pt ON p.patient_id = pt.patient_id 
        JOIN doctors d ON p.doctor_id = d.doctor_id 
        WHERE p.prescription_date = '$today' 
          AND p.status IN ('Issued', 'Partially Issued') 
        ORDER BY p.prescription_id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Print Bills - ගම්පහ බෙහෙත් ශාලාව</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="reception-style.css">
</head>
<body class="reception-body">

<div class="topbar reception-topbar">
    <h2>ගම්පහ බෙහෙත් ශාලාව &mdash; Print Bills</h2>
    <a href="reception_dashboard.php">&larr; Back to Dashboard</a>
</div>

<div class="reception-shell" style="margin-top: 30px;">
    <div class="tokens-wrapper" style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        <h3 style="color: #1b5e20; font-family: 'Zilla Slab', serif; margin-top: 0; margin-bottom: 20px;">Today's Issued Prescriptions (Ready for Billing)</h3>
        
        <table class="tokens-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f1f8e9; color: #1b5e20;">
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #c8e6c9;">Prescription ID</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #c8e6c9;">Patient Name</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #c8e6c9;">Doctor</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #c8e6c9;">Status</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #c8e6c9;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows == 0): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px; color: #888;">
                            No prescriptions issued yet today.
                        </td>
                    </tr>
                <?php else: while ($row = $result->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;"><b>#P-<?php echo str_pad($row['prescription_id'], 4, '0', STR_PAD_LEFT); ?></b></td>
                    <td style="padding: 12px;"><?php echo htmlspecialchars($row['patient_name']); ?></td>
                    <td style="padding: 12px;"><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                    <td style="padding: 12px;">
                        <span class="rec-status rec-status-<?php echo str_replace(' ', '', $row['status']); ?>" style="padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; background: #e8f5e9; color: #2e7d32;">
                            <?php echo $row['status']; ?>
                        </span>
                    </td>
                    <td style="padding: 12px;">
                        <a href="print_bill.php?id=<?php echo $row['prescription_id']; ?>" target="_blank" class="btn" style="background: #1b5e20; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-size: 14px;">
                            🧾 Print Bill
                        </a>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>