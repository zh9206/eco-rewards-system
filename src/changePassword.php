<?php
session_start();
include("conn.php");

if (!isset($_SESSION['UserID'])) {
    header("Location: index.html");
    exit();
}

$userID = $_SESSION['UserID'];

$returnPage = $_POST['return_page'] ?? 'studentDashboard.php';

// Get Form Data
$oldPassword = trim($_POST['old_password'] ?? '');
$newPassword = trim($_POST['new_password'] ?? '');
$confirmPassword = trim($_POST['confirm_password'] ?? '');

if ($oldPassword === "" || $newPassword === "" || $confirmPassword === "") {
    $_SESSION['profile_error'] = "Please fill in all password fields.";
    header("Location: " . $returnPage);
    exit();
}

if ($newPassword !== $confirmPassword) {
    $_SESSION['profile_error'] = "New password and confirm password do not match.";
    header("Location: " . $returnPage);
    exit();
}

$sql = "SELECT Passwords FROM User WHERE UserID = ?";
$stmt = $dbConn->prepare($sql);
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    $_SESSION['profile_error'] = "User not found.";
    header("Location: " . $returnPage);
    exit();
}

if ($user['Passwords'] !== $oldPassword) {
    $_SESSION['profile_error'] = "Old password is incorrect.";
    header("Location: " . $returnPage);
    exit();
}

$updateSql = "UPDATE User SET Passwords = ? WHERE UserID = ?";
$updateStmt = $dbConn->prepare($updateSql);
$updateStmt->bind_param("ss", $newPassword, $userID);

if ($updateStmt->execute()) {
    $_SESSION['profile_success'] = "Password changed successfully.";
} else {
    $_SESSION['profile_error'] = "Failed to change password.";
}

header("Location: " . $returnPage);
exit();
?>