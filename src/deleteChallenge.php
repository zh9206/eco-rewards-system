<?php
include("conn.php");

if(isset($_POST['challengeid'])){
    $challengeid = $_POST['challengeid'];

    $stmt = $dbConn->prepare("DELETE FROM challenge WHERE ChallengeID = ?");
    $stmt->bind_param("s", $challengeid);

    if($stmt->execute()){
        echo "success";
    } else {
        echo "error: " . $stmt->error;
    }

    $stmt->close();
}
?>