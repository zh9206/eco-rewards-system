<?php
include("conn.php");

$result = $dbConn->query("SELECT AnnouncementID FROM announcement ORDER BY AnnouncementID DESC LIMIT 1");

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastId = $row['AnnouncementID'];   // e.g. A01
    $num = intval(substr($lastId, 1)) + 1;
    $newId = "A" . str_pad($num, 3, "0", STR_PAD_LEFT);
} else {
    $newId = "A001";
}

echo $newId;

$dbConn->close();
?>