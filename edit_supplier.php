<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']); // Pharmacist and admin only

if (!isset($_GET['id'])) {
    header("Location: suppliers.php");
    exit();
}

$id = intval($_GET['id']);
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
        $stmt = $conn->prepare("UPDATE suppliers SET supplier_name=?, contact_person=?, contact_number=?, email=?, address=? WHERE supplier_id=?");
        $stmt->bind_param("sssssi", $supplier_name, $contact_person, $contact_number, $email, $address, $id);

        if ($stmt->execute()) {
            header("Location: suppliers.php");
            exit();
        } else {
            $error = "Something went wrong. Try again.";
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: suppliers.php");
    exit();
}
$supplier = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Supplier - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="topbar">
    <h2>Edit Supplier</h2>
    <a href="suppliers.php">&larr; Back to Suppliers</a>
</div>

<div class="container">
    <div class="form-box">
        <h3>Update Supplier Details</h3>
        <form method="POST">
            <label>Supplier Name *</label>
            <input type="text" name="supplier_name" value="<?php echo htmlspecialchars($supplier['supplier_name']); ?>" required>

            <label>Contact Person</label>
            <input type="text" name="contact_person" value="<?php echo htmlspecialchars($supplier['contact_person']); ?>">

            <label>Contact Number</label>
            <input type="text" name="contact_number" value="<?php echo htmlspecialchars($supplier['contact_number']); ?>">

            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($supplier['email']); ?>">

            <label>Address</label>
            <textarea name="address" rows="3"><?php echo htmlspecialchars($supplier['address']); ?></textarea>

            <button type="submit">Update Supplier</button>
            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>
        </form>
    </div>
</div>

</body>
</html>
