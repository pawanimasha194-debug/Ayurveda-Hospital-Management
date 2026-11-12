<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['admin']); // Admin only

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Prevent deleting the main admin account or your own currently logged-in account
    $stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && $user['username'] != 'admin' && $id != $_SESSION['user_id']) {
        $del = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $del->bind_param("i", $id);
        $del->execute();
    }
}

header("Location: manage_users.php");
exit();
?>
