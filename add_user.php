<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['admin']); // Admin only

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $plain_password = trim($_POST['password']);
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];
    $contact_number = trim($_POST['contact_number']);

    if ($username == '' || $plain_password == '' || $full_name == '') {
        $error = "Username, password, and full name are required.";
    } else {
        // Check if username already exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {
            $error = "This username is already taken. Please choose another.";
        } else {
            $hashed = password_hash($plain_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role, contact_number) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $hashed, $full_name, $role, $contact_number);

            if ($stmt->execute()) {
                header("Location: manage_users.php");
                exit();
            } else {
                $error = "Something went wrong. Try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add User - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="topbar">
    <h2>Add New User</h2>
    <a href="manage_users.php">&larr; Back to Users</a>
</div>

<div class="container">
    <div class="form-box">
        <h3>New Login Account</h3>
        <p class="info-note">Create a login for a doctor, pharmacist, or staff member. They will use this username and password to log in.</p>
        <form method="POST">
            <label>Full Name *</label>
            <input type="text" name="full_name" required>

            <label>Username *</label>
            <input type="text" name="username" required>

            <label>Password *</label>
            <input type="password" name="password" required>

            <label>Role *</label>
            <select name="role" required>
                <option value="doctor">Doctor</option>
                <option value="pharmacist">Pharmacist</option>
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
            </select>

            <label>Contact Number</label>
            <input type="text" name="contact_number">

            <button type="submit">Create User</button>
            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>
        </form>
    </div>
</div>

</body>
</html>
