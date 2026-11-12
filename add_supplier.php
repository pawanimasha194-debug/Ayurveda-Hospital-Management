<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']); // Pharmacist and admin only

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supplier_name = trim($_POST['supplier_name']);
    $contact_person = trim($_POST['contact_person']);
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    if ($supplier_name == '') {
        $error = "Supplier name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO suppliers (supplier_name, contact_person, contact_number, email, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $supplier_name, $contact_person, $contact_number, $email, $address);

        if ($stmt->execute()) {
            header("Location: suppliers.php");
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
<title>Add Supplier - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="topbar">
    <h2>Add Supplier</h2>
    <a href="suppliers.php">&larr; Back to Suppliers</a>
</div>

<div class="container">
    <div class="form-box">
        <h3>New Supplier Details</h3>
        <form method="POST">
            <label>Supplier Name *</label>
            <input type="text" name="supplier_name" required>

            <label>Contact Person</label>
            <input type="text" name="contact_person">

            <label>Contact Number</label>
            <input type="text" name="contact_number">

            <label>Email</label>
            <input type="email" name="email">

            <label>Address</label>
            <textarea name="address" rows="3"></textarea>

            <button type="submit">Save Supplier</button>
            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>
        </form>
    </div>
</div>

</body>
</html>
