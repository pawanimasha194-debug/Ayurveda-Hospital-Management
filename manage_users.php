<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['admin']); // Admin only - manages all login accounts

$result = $conn->query("SELECT user_id, username, full_name, role, contact_number, created_at FROM users ORDER BY user_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users - Ayurveda Hospital System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@600;700&family=Karla:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="topbar">
    <h2>Manage Users</h2>
    <a href="dashboard.php" class="back">&larr; Back to Dashboard</a>
</div>

<div class="container">
    <div class="header-row">
        <h3>Login Accounts</h3>
        <a href="add_user.php" class="btn">+ Add New User</a>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Role</th>
            <th>Contact</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['user_id']; ?></td>
            <td><?php echo htmlspecialchars($row['username']); ?></td>
            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
            <td><span class="role-badge role-<?php echo $row['role']; ?>"><?php echo $row['role']; ?></span></td>
            <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
            <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
            <td class="actions">
                <?php if ($row['username'] != 'admin'): ?>
                <a href="delete_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this user account?');">Delete</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
