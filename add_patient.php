<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['reception']); // Reception only - doctors cannot register patients

$error = "";
$success = "";

$from_token  = isset($_GET['from_token']) ? intval($_GET['from_token']) : 0;
$prefill_name  = isset($_GET['name']) ? $_GET['name'] : '';
$prefill_phone = isset($_GET['phone']) ? $_GET['phone'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact_number = trim($_POST['contact_number']);
    $nic_number = trim($_POST['nic_number']);
    $address = trim($_POST['address']);

    if ($full_name == '') {
        $error = "Patient name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO patients (full_name, dob, gender, contact_number, nic_number, address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $full_name, $dob, $gender, $contact_number, $nic_number, $address);

        if ($stmt->execute()) {
    $new_patient_id = $conn->insert_id;
    if ($from_token > 0) {
        // Auto-complete the token and go straight to prescription
        $conn->query("UPDATE tokens SET status='In Consultation', called_at=NOW() WHERE token_id=" . intval($from_token));
        header("Location: add_prescription.php?patient_id=" . $new_patient_id . "&token_id=" . intval($from_token));
    } else {
        header("Location: patients.php");
    }
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
<title>Add Patient - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="topbar">
    <h2>Add Patient</h2>
    <a href="patients.php">&larr; Back to Patients</a>
</div>

<div class="container">
    <div class="form-box">
        <h3>New Patient Details</h3>
        <form method="POST">
            <label>Full Name *</label>
<input type="text" name="full_name" value="<?php echo htmlspecialchars($prefill_name); ?>" required>

            <label>Date of Birth</label>
            <input type="date" name="dob">

            <label>Gender</label>
            <select name="gender">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>

            <label>Contact Number</label>
<input type="text" name="contact_number" value="<?php echo htmlspecialchars($prefill_phone); ?>">

            <label>NIC Number</label>
            <input type="text" name="nic_number">

            <label>Address</label>
            <textarea name="address" rows="3"></textarea>

            <button type="submit">Save Patient</button>
            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>
        </form>
    </div>
</div>

</body>
</html>
