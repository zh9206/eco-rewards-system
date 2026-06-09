<?php
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location: index.html");
    exit();
}

include("conn.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: rewards.php");
    exit();
}

$userID = $_SESSION['UserID'];
$rewardID = trim($_POST['rewardID'] ?? '');

if ($rewardID === '') {
    echo "<script>
        alert('Invalid reward.');
        window.location.href='rewards.php';
    </script>";
    exit();
}

// Get All Rewards
$sqlReward = "SELECT RewardID, Title, Type, Validity, Points
              FROM reward
              WHERE RewardID = ?";
$stmtReward = $dbConn->prepare($sqlReward);
$stmtReward->bind_param("s", $rewardID);
$stmtReward->execute();
$resultReward = $stmtReward->get_result();
$reward = $resultReward->fetch_assoc();

if (!$reward) {
    echo "<script>
        alert('Reward not found.');
        window.location.href='rewards.php';
    </script>";
    exit();
}

// Total Points
$sqlPoints = "SELECT COALESCE(SUM(c.Points), 0) AS TotalPoints
              FROM challenge_submission cs
              JOIN challenge c ON cs.ChallengeID = c.ChallengeID
              WHERE cs.StudentID = ?
              AND cs.Status = 'Approved'";
$stmtPoints = $dbConn->prepare($sqlPoints);
$stmtPoints->bind_param("s", $userID);
$stmtPoints->execute();
$resultPoints = $stmtPoints->get_result();
$pointsRow = $resultPoints->fetch_assoc();
$totalPoints = (int)$pointsRow['TotalPoints'];

// Total Redeemed Points
$sqlRedeemed = "SELECT COALESCE(SUM(r.Points), 0) AS RedeemedPoints
                FROM reward_redemption rr
                JOIN reward r ON rr.RewardID = r.RewardID
                WHERE rr.StudentID = ?";
$stmtRedeemed = $dbConn->prepare($sqlRedeemed);
$stmtRedeemed->bind_param("s", $userID);
$stmtRedeemed->execute();
$resultRedeemed = $stmtRedeemed->get_result();
$redeemedRow = $resultRedeemed->fetch_assoc();
$redeemedPoints = (int)$redeemedRow['RedeemedPoints'];

// Total Remaining Points
$remainingPoints = $totalPoints - $redeemedPoints;
if ($remainingPoints < 0) {
    $remainingPoints = 0;
}

// Generate Redemption ID
$sqlID = "SELECT RedemptionID
          FROM reward_redemption
          ORDER BY RedemptionID DESC
          LIMIT 1";
$resultID = $dbConn->query($sqlID);

if ($resultID && $resultID->num_rows > 0) {
    $row = $resultID->fetch_assoc();
    $lastID = $row['RedemptionID'];
    $num = intval(substr($lastID, 1)) + 1;
    $newRedemptionID = "D" . str_pad($num, 3, "0", STR_PAD_LEFT);
} else {
    $newRedemptionID = "D001";
}

// Calculate Expiry Date
if ($reward['Type'] === 'Voucher') {
    $expiryDate = date('Y-m-d', strtotime("+{$reward['Validity']} days"));
} else {
    $expiryDate = null; // Merchandise
}

// Insert Redemption
$sqlInsert = "INSERT INTO reward_redemption
              (RedemptionID, Redeem_Date, Expiry_Date, Status, StudentID, RewardID)
              VALUES (?, CURDATE(), ?, 'Active', ?, ?)";
$stmtInsert = $dbConn->prepare($sqlInsert);
$stmtInsert->bind_param("ssss", $newRedemptionID, $expiryDate, $userID, $rewardID);

if ($stmtInsert->execute()) {
    $title = htmlspecialchars($reward['Title'], ENT_QUOTES);
    echo "<script>
        alert('Reward redeemed successfully!\\nRedemption ID: $newRedemptionID\\nReward: $title');
        window.location.href='rewards.php';
    </script>";
} else {
    echo "<script>
        alert('Failed to redeem reward.');
        window.location.href='rewards.php';
    </script>";
}

exit();
?>