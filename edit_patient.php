<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Doctor or Reception can edit patients now
restrictTo(['doctor', 'reception']); 

if (!isset($_GET['id'])) {
    header("Location: patients.php");
    exit();
}

$id = intval($_GET['id']);
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact_number = trim($_POST['contact_number']);
    $nic_number = trim($_POST['nic_number']);
    $address = trim($_POST['address']);
    $patient_type = $_POST['patient_type']; // New Field

    if ($full_name == '') {
        $error = "Patient name is required.";
    } else {
        $stmt = $conn->prepare("UPDATE patients SET full_name=?, dob=?, gender=?, contact_number=?, nic_number=?, address=?, patient_type=? WHERE patient_id=?");
        $stmt->bind_param("sssssssi", $full_name, $dob, $gender, $contact_number, $nic_number, $address, $patient_type, $id);

        if ($stmt->execute()) {
            // Redirect back to wherever they came from
            $back = isset($_GET['from']) && $_GET['from'] == 'reception' ? 'reception_dashboard.php' : 'patients.php';
            header("Location: $back");
            exit();
        } else {
            $error = "Something went wrong. Try again.";
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: patients.php");
    exit();
}
$patient = $result->fetch_assoc();
$prefix = ($patient['patient_type'] == 'regular') ? 'R-' : 'T-';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Patient - ගම්පහ බෙහෙත් ශාලාව</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.type-chips { display: flex; gap: 10px; flex-wrap: wrap; }
.type-chips input[type="radio"] { display: none; }
.type-chips label { flex: 1; min-width: 140px; padding: 12px 14px; border: 1.5px solid #dfece0; border-radius: 10px; background: #f8fbf7; text-align: center; cursor: pointer; font-size: 14px; font-weight: 600; color: #5f6f68; transition: all 0.18s ease; text-transform: none; letter-spacing: 0; }
.type-chips label:hover { border-color: #c8e6c9; background: #fff; }
.type-chips input[type="radio"]:checked + label { background: linear-gradient(135deg, #1b5e20, #4caf50); border-color: #1b5e20; color: #fff; box-shadow: 0 6px 16px rgba(27,94,32,0.25); transform: translateY(-2px); }
.type-chips input[type="radio"][value="temporary"]:checked + label { background: linear-gradient(135deg, #c9a227, #d4a017); border-color: #b8860b; box-shadow: 0 6px 16px rgba(201,162,39,0.25); }
</style>
</head>
<body>

<div class="topbar">
    <h2>Edit Patient (<?php echo $prefix . str_pad($patient['patient_id'], 4, '0', STR_PAD_LEFT); ?>)</h2>
    <a href="javascript:history.back()">&larr; Back</a>
</div>

<div class="container">
    <div class="form-box">
        <h3>Update Patient Details</h3>
        <form method="POST">
            <label>Full Name *</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($patient['full_name']); ?>" required>

            <label>Date of Birth</label>
            <input type="date" name="dob" value="<?php echo $patient['dob']; ?>">

            <label>Gender</label>
            <select name="gender">
                <option value="Male" <?php if ($patient['gender']=='Male') echo 'selected'; ?>>Male</option>
                <option value="Female" <?php if ($patient['gender']=='Female') echo 'selected'; ?>>Female</option>
                <option value="Other" <?php if ($patient['gender']=='Other') echo 'selected'; ?>>Other</option>
            </select>

            <label>Contact Number</label>
            <input type="text" name="contact_number" value="<?php echo htmlspecialchars($patient['contact_number']); ?>">

            <label>NIC Number</label>
            <input type="text" name="nic_number" value="<?php echo htmlspecialchars($patient['nic_number']); ?>">

            <label>Address</label>
            <textarea name="address" rows="3"><?php echo htmlspecialchars($patient['address']); ?></textarea>

            <!-- Patient Type Manual Edit -->
            <label style="margin-top: 20px;">Patient Category</label>
            <div class="type-chips" style="margin-bottom: 20px;">
                <input type="radio" name="patient_type" id="type-temp" value="temporary" <?php echo ($patient['patient_type'] == 'temporary') ? 'checked' : ''; ?>>
                <label for="type-temp">🕒 Temporary (One-time)</label>

                <input type="radio" name="patient_type" id="type-reg" value="regular" <?php echo ($patient['patient_type'] == 'regular') ? 'checked' : ''; ?>>
                <label for="type-reg">⭐ Regular Patient</label>
            </div>

            <button type="submit">Update Patient</button>
            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>
        </form>
    </div>
</div>

</body>
</html>