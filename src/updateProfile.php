<?php
session_start();
include("conn.php");

if (!isset($_SESSION['UserID'])) {
    header("Location: index.html");
    exit();
}

$userID = $_SESSION['UserID'];
$returnPage = $_POST['return_page'] ?? 'studentDashboard.php';

$username = trim($_POST['username']);
$gender = trim($_POST['gender']);
$dob = trim($_POST['dob']);
$contact = trim($_POST['contact']);

if ($username === "" || $gender === "" || $dob === "" || $contact === "") {
    $_SESSION['profile_error'] = "Please fill in all profile fields.";
    header("Location: " . $returnPage);
    exit();
}

// DOB validation
$dobTimestamp = strtotime($dob);
$todayTimestamp = strtotime(date('Y-m-d'));
$minTimestamp = strtotime('-100 years', $todayTimestamp);
$maxTimestamp = strtotime('-16 years', $todayTimestamp);

if ($dobTimestamp < $minTimestamp || $dobTimestamp > $maxTimestamp) {
    $_SESSION['profile_error'] = "Please enter a realistic date of birth.";
    header("Location: " . $returnPage);
    exit();
}

$sql = "UPDATE User SET Username = ?, Gender = ?, DOB = ?, Contact = ? WHERE UserID = ?";
$stmt = $dbConn->prepare($sql);
$stmt->bind_param("sssss", $username, $gender, $dob, $contact, $userID);

if ($stmt->execute()) {
    $_SESSION['profile_success'] = "Profile updated successfully.";
    $_SESSION['Username'] = $username;
} else {
    $_SESSION['profile_error'] = "Failed to update profile.";
}

header("Location: " . $returnPage);
exit();
?>