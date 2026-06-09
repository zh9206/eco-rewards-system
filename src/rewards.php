<?php
session_start();


if (!isset($_SESSION['UserID'])) {
    header("Location: index.html");
    exit();
}

include ("conn.php");

$userID = $_SESSION['UserID'];

// Get User's Profile
$sqlUser = "SELECT User.*, Role.Role_Name
            FROM User
            JOIN Role ON User.RoleID = Role.RoleID
            WHERE User.UserID = ?";

$stmtUser = $dbConn->prepare($sqlUser);
$stmtUser->bind_param("s", $userID);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$user = $resultUser->fetch_assoc();

// Get Total Points
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
$totalPoints = $pointsRow['TotalPoints'];

// Get Total Redeemed Points
$sqlRedeemed = "SELECT COALESCE(SUM(r.Points), 0) AS RedeemedPoints
                FROM reward_redemption rd
                JOIN reward r ON rd.RewardID = r.RewardID
                WHERE rd.StudentID = ?";

$stmtRedeemed = $dbConn->prepare($sqlRedeemed);
$stmtRedeemed->bind_param("s", $userID);
$stmtRedeemed->execute();
$resultRedeemed = $stmtRedeemed->get_result();
$redeemedRow = $resultRedeemed->fetch_assoc();
$redeemedPoints = $redeemedRow['RedeemedPoints'];

// Get Total Remaining Points
$remainingPoints = $totalPoints - $redeemedPoints;
if ($remainingPoints < 0) {
    $remainingPoints = 0;
}

// Get All Rewards
$sqlRewards = "SELECT RewardID, Title, Type, Validity, Points
               FROM reward
               ORDER BY Points ASC";

$resultRewards = $dbConn->query($sqlRewards);
$rewards = [];
while ($row = $resultRewards->fetch_assoc()) {
    $rewards[] = $row;
}

// Update Redemption Status
$sqlExpire = "
    UPDATE reward_redemption
    SET Status = 'Expired'
    WHERE Expiry_Date IS NOT NULL
    AND Expiry_Date < CURDATE()
    AND Status = 'Active'
";

$dbConn->query($sqlExpire);

// Get Redeemed History
$sqlHistory = "SELECT rr.RedemptionID, rr.Redeem_Date, rr.Expiry_Date, rr.Status,
                      r.Title, r.Type, r.Points
               FROM reward_redemption rr
               JOIN reward r ON rr.RewardID = r.RewardID
               WHERE rr.StudentID = ?
               ORDER BY rr.Redeem_Date DESC";

$stmtHistory = $dbConn->prepare($sqlHistory);
$stmtHistory->bind_param("s", $userID);
$stmtHistory->execute();
$resultHistory = $stmtHistory->get_result();

include("profile.php");

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="responsive.css">
<title>Rewards Shop - <?php echo $user['Username']?></title>
<style>

.shop-header {
    margin-bottom: 25px;
}

.shop-header h1 {
    color: #2e7d32;
    margin-bottom: 10px;
}

.history-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
    z-index: 999;
}

.history-content {
    position: relative;
    background: white;
    width: 850px;
    max-width: 95%;
    max-height: 80vh;
    overflow-y: auto;
    border-radius: 18px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
}

.history-content h2 {
    margin-bottom: 20px;
    color: #2e7d32;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
    border-radius: 16px;
    overflow: hidden; 
}

.history-table th,
.history-table td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: center;
}

.history-table th {
    background: #84B179;
    color: white;
}

.history-table tr:nth-child(even) {
    background: #f9f9f9;
}

.status-active {
    color: green;
    font-weight: bold;
}

.status-used {
    color: #b26a00;
    font-weight: bold;
}

.status-expired {
    color: red;
    font-weight: bold;
}

.points-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 10px;
}

.points-summary .big-points {
    font-size: 40px;
    font-weight: bold;
    color: #1f3d2b;
}

.points-summary .big-points span {
    font-size: 18px;
    font-weight: normal;
    color: #666;
    margin-left: 5px;
}

.points-info {
    text-align: right;
    color: #6b7280;
    font-size: 18px;
    line-height: 1.8;
}

.points-info strong {
    color: #1f2937;
    font-size: 22px;
}

/* rewards grid */
.rewards-grid {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.reward-item {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 6px 16px rgba(0,0,0,0.08);
    border: 1px solid #eee;
    padding: 20px 24px;
}

.reward-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.reward-info {
    flex: 1;
}

.reward-points {
    font-size: 18px;
    font-weight: bold;
    color: #7b8b9d;
    margin-bottom: 8px;
}

.reward-title {
    font-size: 22px;
    font-weight: bold;
    color: #253043;
    margin-bottom: 8px;
}

.reward-type,
.reward-validity {
    font-size: 14px;
    color: #666;
    margin-bottom: 4px;
}

.reward-action {
    display: flex;
    align-items: center;
    justify-content: center;
}

.reward-btn {
    background: #155e63;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 10px;
    font-weight: bold;
    cursor: pointer;
}

.reward-btn:disabled {
    background: #d8dde5;
    cursor: not-allowed;
    color: white;
}
</style>
</head>
<body>
    
    <!-- Hamburger Button - Small Screen Only!! -->
    <?php include("hamburger.php")?>

    <div class="sidebar" id="sidebar">
        
        <div class="sidebar-top">
            <div class="logo">
                <a href="studentDashboard.php">
                    <img src="logo.png" alt="Logo" >
                </a>    
            </div>

            <div class="nav-links">
                <a href="logAction.php">Log Action</a>
                <a href="leaderboard.php">Leaderboard</a>
                <a href="rewards.php"  class="active">Rewards Shop</a>
            </div>
        </div>

        <div class="sidebar-bottom">
            <div class="profile" onclick="openProfileModal()">
                <img src="profile-pic.png" alt="Profile Pic">
                <span><?php echo $user['Username']; ?></span>
            </div>

            <button class="btn" onclick="logout()">Logout</button>
        </div>
    </div>

    <div class="main">
        <div class="section">
            <div class="shop-header">
                <h1>Rewards Shop</h1>
                <button class="btn redemptionBtn" onclick="openHistoryModal()">Redemption History</button>

                <div class="points-summary">
                    <div class="big-points">
                        <?php echo $remainingPoints; ?><span>Points Remaining</span>
                    </div>

                    <div class="points-info">
                        <p><strong><?php echo $redeemedPoints; ?></strong> Points Redeemed</p>
                        <p><strong><?php echo $totalPoints; ?></strong> Total Earned</p>
                    </div>
                </div>
            </div>

            <div class="filter-container">
                <select class="filter-dropdown" onchange="filterRewards(this.value)">
                    <option value="all">All</option>
                    <option value="merchandise">Merchandise</option>
                    <option value="voucher">Voucher</option>
                </select>
            </div>

            <div class="rewards-grid">
                <?php foreach ($rewards as $reward): ?>
                <?php
                    $canRedeem = $remainingPoints >= $reward['Points'];
                ?>
                <div class="reward-item" data-type="<?php echo strtolower($reward['Type']); ?>">
                    <div class="reward-body">
                        <div class="reward-info">
                            <div class="reward-points"><?php echo htmlspecialchars($reward['Points']); ?> Points</div>
                            <div class="reward-title"><?php echo htmlspecialchars($reward['Title']); ?></div>
                            <div class="reward-type">Type: <?php echo htmlspecialchars($reward['Type']); ?></div>
                            <div class="reward-validity">Validity: <?php echo htmlspecialchars($reward['Validity']);?> day</div>
                        </div>

                        <div class="reward-action">
                            <form action="redeemReward.php" method="POST" onsubmit="return confirm('Are you sure you want to redeem this reward?');">
                                <input type="hidden" name="rewardID" value="<?php echo $reward['RewardID']; ?>">
                                <button type="submit" class="reward-btn" <?php echo $canRedeem ? "" : "disabled"; ?>>
                                    Redeem
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>

        </div>
    </div>

    <div id="historyModal" class="history-modal">
        <div class="history-content">
            <span class="close-btn" onclick="closeHistoryModal()">✖</span>
            <h2>Redemption History</h2>

            <?php if ($resultHistory->num_rows > 0): ?>
                <table class="history-table">
                    <tr>
                        <th>Reward</th>
                        <th>Type</th>
                        <th>Points</th>
                        <th>Redeem Date</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                    </tr>

                    <?php while ($history = $resultHistory->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($history['Title']); ?></td>
                            <td><?php echo htmlspecialchars($history['Type']); ?></td>
                            <td><?php echo htmlspecialchars($history['Points']); ?></td>
                            <td><?php echo htmlspecialchars($history['Redeem_Date']); ?></td>
                            <td><?php echo htmlspecialchars($history['Expiry_Date']); ?></td>
                            <td><?php echo htmlspecialchars($history['Status']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <p>No redeemed rewards yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Filter
        function filterRewards(type) {
            const items = document.querySelectorAll(".reward-item");

            items.forEach(item => {
                const itemType = item.getAttribute("data-type");

                if (type === "all" || itemType === type) {
                    item.style.display = "block";
                } else {
                    item.style.display = "none";
                }
            });
        }

        function openHistoryModal() {
            document.getElementById("historyModal").style.display = "flex";
        }

        function closeHistoryModal() {
            document.getElementById("historyModal").style.display = "none";
        }

        function logout() {
            const confirmAction = confirm("Are you sure you want to logout?");
            
            if (confirmAction) {
                window.location.href = "logout.php";
            }
        }
    </script>
    <script src="hamburger.js"></script>
</body>
</html>