<?php
include("conn.php");

if(isset($_POST['userid'])){
    $userid = $_POST['userid'];

    $stmt = $dbConn->prepare("DELETE FROM user WHERE UserID = ?");
    $stmt->bind_param("s", $userid);

    if($stmt->execute()){
        echo "success";
    } else {
        echo "error: " . $stmt->error;
    }

    $stmt->close();
}
?>