<?php
session_start();

include("conn.php");

$userID = $_SESSION['UserID'];

$stmt = $dbConn->prepare("
SELECT User.*, Role.Role_Name
FROM User
JOIN Role ON User.RoleID = Role.RoleID
WHERE User.UserID = ?
");

$stmt->bind_param("s",$userID);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo json_encode($user);
?>