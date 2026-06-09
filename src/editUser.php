<?php
include('conn.php');

$id = $_POST['userid'];
$name = $_POST['username'];
$gender = $_POST['gender'];
$dob = $_POST['dob'];
$contact = $_POST['contact'];
$role = $_POST['roleid'];
$password = $_POST['password'] ?? '';

if ($password === "") {
    $sql = "UPDATE user SET 
                Username = ?, 
                Gender = ?, 
                DOB = ?, 
                Contact = ?, 
                RoleID = ?
            WHERE UserID = ?";
    $stmt = $dbConn->prepare($sql);
    $stmt->bind_param("ssssss", $name, $gender, $dob, $contact, $role, $id);
} else {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "UPDATE user SET 
                Username = ?, 
                Gender = ?, 
                DOB = ?, 
                Contact = ?, 
                RoleID = ?,
                Passwords = ?
            WHERE UserID = ?";
    $stmt = $dbConn->prepare($sql);
    $stmt->bind_param("sssssss", $name, $gender, $dob, $contact, $role, $hashedPassword, $id);
}

if ($stmt->execute()) {
    echo "success";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$dbConn->close();
?>