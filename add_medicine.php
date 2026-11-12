<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']); // Pharmacist and admin only

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $medicine_name = trim($_POST['medicine_name']);
    $medicine_type = trim($_POST['medicine_type']);
    $unit = trim($_POST['unit']);
    $reorder_level = intval($_POST['reorder_level']);
    $unit_price = floatval($_POST['unit_price']);
    $current_stock = intval($_POST['current_stock']);

    if ($medicine_name == '') {
        $error = "Medicine name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO medicines (medicine_name, medicine_type, unit, reorder_level, unit_price, current_stock) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssidi", $medicine_name, $medicine_type, $unit, $reorder_level, $unit_price, $current_stock);

        if ($stmt->execute()) {
            header("Location: medicines.php");
            exit();
        } else {
            $error = "Something went wrong. Try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Medicine - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="topbar">
    <h2>Add Medicine</h2>
    <a href="medicines.php">&larr; Back to Medicines</a>
</div>

<div class="container">
    <div class="form-box">
        <h3>New Medicine Details</h3>
        <form method="POST">
            <label>Medicine Name *</label>
            <input type="text" name="medicine_name" required>

            <label>Type</label>
            <input type="text" name="medicine_type" placeholder="e.g. Choornam, Thailam, Kashayam">

            <label>Unit</label>
            <input type="text" name="unit" placeholder="e.g. bottle, packet, box">

            <label>Reorder Level</label>
            <input type="number" name="reorder_level" value="10">

            <label>Unit Price (Rs.)</label>
            <input type="number" step="0.01" name="unit_price" value="0.00">

            <label>Opening Stock Quantity</label>
            <input type="number" name="current_stock" value="0">

            <button type="submit">Save Medicine</button>
            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>
        </form>
    </div>
</div>

</body>
</html>
