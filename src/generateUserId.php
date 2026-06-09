<?php
include("conn.php");

$roleid = isset($_GET['roleid']) ? $_GET['roleid'] : 'R01';

// Moderator & Admin -> Uxxx ; Student -> TPxxx
$prefix = in_array($roleid, ['R02','R03']) ? 'U' : 'TP';

$sql = "SELECT UserID FROM user WHERE UserID LIKE ? ORDER BY UserID DESC LIMIT 1";
$stmt = $dbConn->prepare($sql);
$likePattern = $prefix . "%";
$stmt->bind_param("s", $likePattern);
$stmt->execute();
$result = $stmt->get_result();

if($result && $result->num_rows > 0){
    $row = $result->fetch_assoc();
    $lastId = $row['UserID'];
    $num = intval(substr($lastId, strlen($prefix))) + 1;
} else {
    $num = 1;
}

$newId = $prefix . str_pad($num, 3, "0", STR_PAD_LEFT);

echo $newId;

$stmt->close();
$dbConn->close();
?>