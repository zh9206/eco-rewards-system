<?php
include("conn.php");

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $rewardId = $_POST['RewardID'];
    $title = $_POST['Title'];
    $type = $_POST['Type'];
    $validity = $_POST['Validity'];
    $points = $_POST['Points'];

    $sql = "UPDATE reward 
            SET Title=?, Type=?, Validity=?, Points=? 
            WHERE RewardID=?";
    $stmt = $dbConn->prepare($sql);
    $stmt->bind_param("ssiss", $title, $type, $validity, $points, $rewardId); 
    if($stmt->execute()){
        echo "success";
    } else {
        echo "error";
    }
    $stmt->close();
}
$dbConn->close();
?>