<?php
include("conn.php");

if (!isset($_POST['rewardid'])) {
    echo "Missing reward ID";
    exit();
}

$rewardId = $_POST['rewardid'];

// Check whether reward has redemption records
$checkSql = "SELECT 1 FROM reward_redemption WHERE RewardID = ? LIMIT 1";
$stmtCheck = $dbConn->prepare($checkSql);
$stmtCheck->bind_param("s", $rewardId);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck->num_rows > 0) {
    echo "This reward has already been redeemed and cannot be deleted.";
    exit();
}

// Delete
$deleteSql = "DELETE FROM reward WHERE RewardID = ?";
$stmtDelete = $dbConn->prepare($deleteSql);
$stmtDelete->bind_param("s", $rewardId);

if ($stmtDelete->execute()) {
    echo "success";
} else {
    echo "Delete failed";
}

$stmtCheck->close();
$stmtDelete->close();
$dbConn->close();
?>