<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']);

if (!isset($_GET['prescription_id']) && !isset($_POST['prescription_id'])) {
    header("Location: prescriptions.php");
    exit();
}

$prescription_id = isset($_POST['prescription_id']) ? intval($_POST['prescription_id']) : intval($_GET['prescription_id']);
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $detail_ids = $_POST['detail_id'] ?? [];
    $medicine_ids = $_POST['medicine_id'] ?? [];
    $prescribed_qtys = $_POST['prescribed_qty'] ?? [];
    $issue_qtys = $_POST['issue_qty'] ?? [];

    $conn->begin_transaction();
    try {
        $allFullyIssued = true;
        $anyIssued = false;

        for ($i = 0; $i < count($detail_ids); $i++) {
            $qtyToIssue = intval($issue_qtys[$i]);
            if ($qtyToIssue <= 0) {
                if (intval($prescribed_qtys[$i]) > 0) $allFullyIssued = false;
                continue;
            }

            $med_id = intval($medicine_ids[$i]);

            $stockCheck = $conn->prepare("SELECT current_stock FROM medicines WHERE medicine_id = ?");
            $stockCheck->bind_param("i", $med_id);
            $stockCheck->execute();
            $stockResult = $stockCheck->get_result()->fetch_assoc();

            if ($stockResult['current_stock'] < $qtyToIssue) {
                throw new Exception("Not enough stock for one of the medicines.");
            }

            $issued_by = $_SESSION['user_id'];
            $stmt = $conn->prepare("INSERT INTO medicine_issuing (prescription_id, medicine_id, quantity_issued, issued_date, issued_by) VALUES (?, ?, ?, CURDATE(), ?)");
            $stmt->bind_param("iiii", $prescription_id, $med_id, $qtyToIssue, $issued_by);
            $stmt->execute();

            $stmt2 = $conn->prepare("UPDATE medicines SET current_stock = current_stock - ? WHERE medicine_id = ?");
            $stmt2->bind_param("ii", $qtyToIssue, $med_id);
            $stmt2->execute();

            $anyIssued = true;
            if ($qtyToIssue < intval($prescribed_qtys[$i])) {
                $allFullyIssued = false;
            }
        }

        if ($anyIssued) {
            $status = $allFullyIssued ? 'Issued' : 'Partially Issued';
            $stmt3 = $conn->prepare("UPDATE prescriptions SET status = ? WHERE prescription_id = ?");
            $stmt3->bind_param("si", $status, $prescription_id);
            $stmt3->execute();
        }

        $conn->commit();
        header("Location: view_prescription.php?id=" . $prescription_id);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

$stmt = $conn->prepare("SELECT p.*, pt.full_name AS patient_name
                         FROM prescriptions p
                         JOIN patients pt ON p.patient_id = pt.patient_id
                         WHERE p.prescription_id = ?");
$stmt->bind_param("i", $prescription_id);
$stmt->execute();
$prescription = $stmt->get_result()->fetch_assoc();

if (!$prescription) {
    header("Location: prescriptions.php");
    exit();
}

// ONLY IN-HOUSE MEDICINES
$sql = "SELECT pd.detail_id, pd.medicine_id, pd.prescribed_quantity, pd.dosage_instructions,
               m.medicine_name, m.unit, m.current_stock,
               COALESCE((SELECT SUM(quantity_issued) FROM medicine_issuing mi WHERE mi.prescription_id = pd.prescription_id AND mi.medicine_id = pd.medicine_id), 0) AS already_issued
        FROM prescription_details pd
        JOIN medicines m ON pd.medicine_id = m.medicine_id
        WHERE pd.prescription_id = ? AND pd.dispense_type = 'inhouse'";
$stmt2 = $conn->prepare($sql);
$stmt2->bind_param("i", $prescription_id);
$stmt2->execute();
$details = $stmt2->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Issue Medicines - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="photo-prescriptions">

<div class="topbar">
    <h2>Issue In-House Medicines</h2>
    <a href="view_prescription.php?id=<?php echo $prescription_id; ?>">&larr; Back to Prescription</a>
</div>

<div class="container">
    <div class="card">
        <h3>Prescription #<?php echo $prescription_id; ?> &mdash; <?php echo htmlspecialchars($prescription['patient_name']); ?></h3>
        <p class="info-note">Only <b>in-house</b> medicines are shown here. External medicines must be purchased by the patient from an outside pharmacy.</p>

        <?php if ($details->num_rows == 0): ?>
            <div class="empty-msg">No in-house medicines in this prescription.</div>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="prescription_id" value="<?php echo $prescription_id; ?>">
            <table>
                <tr>
                    <th>Medicine</th>
                    <th>Prescribed</th>
                    <th>Already Issued</th>
                    <th>Stock Available</th>
                    <th>Issue Qty Now</th>
                </tr>
                <?php while ($row = $details->fetch_assoc()):
                    $remaining = $row['prescribed_quantity'] - $row['already_issued'];
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
                    <td><?php echo $row['prescribed_quantity']; ?> <?php echo htmlspecialchars($row['unit']); ?></td>
                    <td><?php echo $row['already_issued']; ?></td>
                    <td>
                        <?php echo $row['current_stock']; ?>
                        <?php if ($row['current_stock'] < $remaining): ?>
                            <div class="low-warn">Insufficient stock!</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <input type="hidden" name="detail_id[]" value="<?php echo $row['detail_id']; ?>">
                        <input type="hidden" name="medicine_id[]" value="<?php echo $row['medicine_id']; ?>">
                        <input type="hidden" name="prescribed_qty[]" value="<?php echo $row['prescribed_quantity']; ?>">
                        <?php if ($remaining <= 0): ?>
                            <span class="done-tag">Fully Issued</span>
                        <?php else: ?>
                            <input type="number" name="issue_qty[]" min="0" max="<?php echo max($remaining, $row['current_stock']); ?>" value="<?php echo min($remaining, $row['current_stock']); ?>">
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>

            <button type="submit">Confirm Issue</button>
            <?php if ($error): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
        </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>