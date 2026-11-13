<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']);

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $medicine_id = intval($_POST['medicine_id']);
    $supplier_id = intval($_POST['supplier_id']);
    $batch_no = trim($_POST['batch_no']); // Meeka dan Auto Generate wela enne
    $quantity = intval($_POST['quantity']);
    $expiry_date = $_POST['expiry_date'];
    $received_date = $_POST['received_date'];

    if ($medicine_id == 0 || $supplier_id == 0 || $quantity <= 0 || $expiry_date == '') {
        $error = "Please fill in medicine, supplier, quantity and expiry date correctly.";
    } else {
        $conn->begin_transaction();
        try {
            // 1. Insert into medicine_batches
            $stmt = $conn->prepare("INSERT INTO medicine_batches (medicine_id, batch_no, quantity, expiry_date, received_date, supplier_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isissi", $medicine_id, $batch_no, $quantity, $expiry_date, $received_date, $supplier_id);
            $stmt->execute();
            $batch_id = $conn->insert_id;

            // 2. Insert into medicine_receiving
            $received_by = $_SESSION['user_id'];
            $stmt2 = $conn->prepare("INSERT INTO medicine_receiving (medicine_id, supplier_id, batch_id, quantity_received, received_date, received_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param("iiiisi", $medicine_id, $supplier_id, $batch_id, $quantity, $received_date, $received_by);
            $stmt2->execute();

            // 3. Update current_stock in medicines table
            $stmt3 = $conn->prepare("UPDATE medicines SET current_stock = current_stock + ? WHERE medicine_id = ?");
            $stmt3->bind_param("ii", $quantity, $medicine_id);
            $stmt3->execute();

            $conn->commit();
            header("Location: receive_medicine.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Something went wrong. Try again.";
        }
    }
}

// Get medicines and suppliers for dropdowns
$medicines = $conn->query("SELECT medicine_id, medicine_name, medicine_type FROM medicines ORDER BY medicine_name");
$suppliers = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receive Stock - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<script src="theme-toggle.js"></script>
</head>
<body>

<div class="topbar">
    <h2>Receive New Stock</h2>
    <a href="receive_medicine.php">&larr; Back to Receiving</a>
</div>

<div class="container">
    <div class="form-box">
        <h3>Record Medicine Received from Supplier</h3>
        <p class="info-note">This will create a new batch (with auto-generated Batch Code) and automatically add the quantity to the medicine's current stock.</p>
        
        <form method="POST">
            <label>Medicine *</label>
            <select name="medicine_id" id="medicineSelect" required onchange="generateBatchNo()">
                <option value="">-- Select Medicine --</option>
                <?php while ($m = $medicines->fetch_assoc()): ?>
                    <option value="<?php echo $m['medicine_id']; ?>" data-type="<?php echo htmlspecialchars($m['medicine_type']); ?>">
                        <?php echo htmlspecialchars($m['medicine_name']); ?> (<?php echo htmlspecialchars($m['medicine_type']); ?>)
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Supplier *</label>
            <select name="supplier_id" required>
                <option value="">-- Select Supplier --</option>
                <?php while ($s = $suppliers->fetch_assoc()): ?>
                    <option value="<?php echo $s['supplier_id']; ?>"><?php echo htmlspecialchars($s['supplier_name']); ?></option>
                <?php endwhile; ?>
            </select>

            <label>Batch No (Auto-generated) *</label>
            <input type="text" name="batch_no" id="batchNoInput" placeholder="Select a medicine first..." readonly style="background:#eef3ec; font-weight:bold; color:#1b5e20;" required>

            <label>Quantity Received *</label>
            <input type="number" name="quantity" min="1" required>

            <label>Expiry Date *</label>
            <input type="date" name="expiry_date" required>

            <label>Received Date *</label>
            <input type="date" name="received_date" value="<?php echo date('Y-m-d'); ?>" required>

            <button type="submit">Save Receiving Record</button>
            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
// JavaScript eken Auto Batch Code eka hadanawa
function generateBatchNo() {
    const select = document.getElementById('medicineSelect');
    const batchInput = document.getElementById('batchNoInput');
    
    if (select.value === "") {
        batchInput.value = "";
        return;
    }

    // Selected option eken medicine type eka gannawa (Churna, Arishta etc.)
    const selectedOption = select.options[select.selectedIndex];
    const medType = selectedOption.getAttribute('data-type').toUpperCase();
    
    // Type eken mula akuru 3 gannawa (CHU, ARI, THA...)
    let prefix = "MED";
    if (medType.includes("CHU")) prefix = "CHU";
    else if (medType.includes("ARI")) prefix = "ARI";
    else if (medType.includes("THA")) prefix = "THA";
    else if (medType.includes("KAS")) prefix = "KAS";
    else if (medType.includes("GUL")) prefix = "GUL";
    else if (medType.includes("GHR")) prefix = "GHR";
    else if (medType.includes("LEH")) prefix = "LEH";
    else if (medType.includes("KAL")) prefix = "KAL";
    else prefix = medType.substring(0, 3);

    // Year eka gannawa (2024)
    const year = new Date().getFullYear();
    
    // Random 3 digit number ekak hadanawa (001 - 999)
    // (Sathi kiyala meka lesiyenma niwaradi karanawa)
    const randomNum = Math.floor(1 + Math.random() * 999);
    const paddedNum = randomNum.toString().padStart(3, '0');

    // Format: CHU-2024-015
    batchInput.value = `${prefix}-${year}-${paddedNum}`;
}

// Page load weddi hari select weddi hari call karanna
window.onload = generateBatchNo;
</script>

</body>
</html>