<?php
include("conn.php");

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $required = ['userid','username','gender','dob','contact','roleid','password'];
    foreach($required as $field){
        if(empty($_POST[$field])){
            echo "Error: $field is required";
            exit;
        }
    }

    $userid   = $_POST['userid'];
    $username = $_POST['username'];
    $gender   = $_POST['gender'];
    $dob      = $_POST['dob'];
    $contact  = $_POST['contact'];
    $roleid   = $_POST['roleid'];
    $password = $_POST['password'];

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $lastActive = date('Y-m-d H:i:s');

    $stmt = $dbConn->prepare("INSERT INTO User (UserID, Username, Gender, Passwords, DOB, Contact, RoleID, LastActive) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if(!$stmt){
        echo "Prepare failed: ".$dbConn->error;
        exit;
    }

    $stmt->bind_param("ssssssss", $userid, $username, $gender, $passwordHash, $dob, $contact, $roleid, $lastActive);

    if($stmt->execute()){
        echo "success";
    } else {
        echo "Error: ".$stmt->error;
    }

    $stmt->close();
    $dbConn->close();
}
?>