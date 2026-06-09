<?php
include('conn.php');

$result = $dbConn->query("SELECT RewardID FROM reward ORDER BY RewardID DESC LIMIT 1");
if($result && $result->num_rows > 0){
    $last = $result->fetch_assoc();
    $num = intval(substr($last['RewardID'],1)) + 1;
    $newId = "R".str_pad($num,3,"0",STR_PAD_LEFT);
} else {
    $newId = "R001";
}

echo $newId;

$dbConn->close();
?>