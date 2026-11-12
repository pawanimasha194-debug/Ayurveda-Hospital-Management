<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['doctor']); 

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $specialization = trim($_POST['specialization']);
    $consultation_fee = floatval($_POST['consultation_fee']);
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $registration_no = trim($_POST['registration_no']);

    if ($full_name == '') {
        $error = "Doctor name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO doctors (full_name, specialization, consultation_fee, contact_number, email, registration_no) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsss", $full_name, $specialization, $consultation_fee, $contact_number, $email, $registration_no);

        if ($stmt->execute()) {
            header("Location: doctors.php");
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
<title>Add Doctor - ගම්පහ බෙහෙත් ශාලාව</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="topbar">
    <h2>Add Doctor</h2>
    <a href="doctors.php">&larr; Back to Doctors</a>
</div>

<div class="container">
    <div class="form-box">
        <h3>New Doctor Details</h3>
        <form method="POST">
            <label>Full Name *</label>
            <input type="text" name="full_name" required>

            <label>Specialization</label>
            <input type="text" name="specialization" placeholder="e.g. Ayurvedic Physician">

            <label>Consultation Fee (Rs.)</label>
            <input type="number" step="0.01" name="consultation_fee" value="1000.00" required>

            <label>Contact Number</label>
            <input type="text" name="contact_number">

            <label>Email</label>
            <input type="email" name="email">

            <label>Registration No</label>
            <input type="text" name="registration_no">

            <button type="submit">Save Doctor</button>
            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>
        </form>
    </div>
</div>

</body>
</html>