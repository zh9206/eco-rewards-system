<?php
include('conn.php');

$sql = "SELECT ChallengeID
        FROM challenge
        ORDER BY CAST(SUBSTRING(ChallengeID, 2) AS UNSIGNED) DESC
        LIMIT 1";

$result = $dbConn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastId = intval(substr($row['ChallengeID'], 1));
    $newId = "C" . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
} else {
    $newId = "C001";
}

echo $newId;

$dbConn->close();
?>