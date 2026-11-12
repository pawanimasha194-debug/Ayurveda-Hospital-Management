<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']); // Pharmacist and admin only

if (!isset($_GET['id'])) {
    header("Location: medicines.php");
    exit();
}

$id = intval($_GET['id']);
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
        $stmt = $conn->prepare("UPDATE medicines SET medicine_name=?, medicine_type=?, unit=?, reorder_level=?, unit_price=?, current_stock=? WHERE medicine_id=?");
        $stmt->bind_param("sssidii", $medicine_name, $medicine_type, $unit, $reorder_level, $unit_price, $current_stock, $id);

        if ($stmt->execute()) {
            header("Location: medicines.php");
            exit();
        } else {
            $error = "Something went wrong. Try again.";
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM medicines WHERE medicine_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: medicines.php");
    exit();
}
$medicine = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Medicine - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="topbar">
    <h2>Edit Medicine</h2>
    <a href="medicines.php">&larr; Back to Medicines</a>
</div>

<div class="container">
    <div class="form-box">
        <h3>Update Medicine Details</h3>
        <form method="POST">
            <label>Medicine Name *</label>
            <input type="text" name="medicine_name" value="<?php echo htmlspecialchars($medicine['medicine_name']); ?>" required>

            <label>Type</label>
            <input type="text" name="medicine_type" value="<?php echo htmlspecialchars($medicine['medicine_type']); ?>">

            <label>Unit</label>
            <input type="text" name="unit" value="<?php echo htmlspecialchars($medicine['unit']); ?>">

            <label>Reorder Level</label>
            <input type="number" name="reorder_level" value="<?php echo $medicine['reorder_level']; ?>">

            <label>Unit Price (Rs.)</label>
            <input type="number" step="0.01" name="unit_price" value="<?php echo $medicine['unit_price']; ?>">

            <label>Current Stock Quantity</label>
            <input type="number" name="current_stock" value="<?php echo $medicine['current_stock']; ?>">

            <button type="submit">Update Medicine</button>
            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>
        </form>
    </div>
</div>

</body>
</html>
